<?php

namespace App\Controller;

use App\Entity\License;
use App\Entity\LicenseProduct;
use App\Entity\LicensePurchase;
use App\Repository\ContactRepository;
use App\Repository\ItemRepository;
use App\Repository\ItemUnitRepository;
use App\Repository\LicenseClientNotificationRepository;
use App\Repository\LicenseProductRepository;
use App\Repository\LicensePurchaseRepository;
use App\Repository\LicenseRepository;
use App\Service\SecurityTools;
use DateTime;
use DateTimeImmutable;
use Error;
use Stripe\Checkout\Session;
use Stripe\Price;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LicenseController extends AbstractController
{
    public function __construct(
        private readonly LicenseRepository $licenseRepository,
        private readonly LicenseProductRepository $licenseProductRepository,
        private readonly LicensePurchaseRepository $licensePurchaseRepository,
        private readonly ContactRepository $contactRepository,
        private readonly TranslatorInterface $translator,
        private readonly HttpClientInterface $httpClient,
        private readonly ItemRepository $itemRepository,
        private readonly SecurityTools $securityTools,
    ) {
    }

    #[Route('/license/check/{licenseHolder}', name: 'license_check', methods: ['GET'])]
    public function check(
        string $licenseHolder,
        Request $request,
    ) : Response {
        // make sure we are on the license server instance
        $self = ($request->server->getBoolean('HTTPS') ? 'https://' : 'http://')
            . $request->server->get('HTTP_HOST');

        if ($this->getParameter('license.server') != $self) {
            throw new NotFoundHttpException();
        }

        // access to license server is only allowed from instance servers
        if (!$this->securityTools->checkIpRestrictedAccess($request)) {
            throw new NotFoundHttpException();
        }

        $license = $this->licenseRepository->findActiveLicense($licenseHolder);
        if (!$license) {
            return $this->json([
                'license' => [
                    'isValid' => false,
                    'dateValid' => '0000-00-00',
                ],
                'message' => 'license not found'
            ]);
        }

        if (!$license->isValid()) {
            return $this->json([
                'license' => [
                    'isValid' => false,
                    'dateValid' => $license->getDateValid()
                        ? $license->getDateValid()->format('Y-m-d H:i:s')
                        : 'lifetime',
                    'isTrial' => $license->getComment() === 'trial'
                ],
                'message' => $this->translator->trans('license.messages.expired'),
            ]);
        }

        return $this->json([
            'license' => [
                'isValid' => true,
                'dateValid' => $license->getDateValid()
                    ? $license->getDateValid()->format('Y-m-d H:i:s')
                    : 'lifetime',
                'isTrial' => $license->getComment() === 'trial',
            ],
            'message' => '',
        ]);
    }

    #[Route('/license/info/{licenseHolder}', name: 'license_info', methods: ['GET'])]
    public function info(
        string $licenseHolder,
        Request $request,
    ) : Response {
        // make sure we are on the license server instance
        $self = ($request->server->getBoolean('HTTPS') ? 'https://' : 'http://')
            . $request->server->get('HTTP_HOST');

        if ($this->getParameter('license.server') != $self) {
            throw new NotFoundHttpException();
        }

        // access to license server is only allowed from instance servers
        if (!$this->securityTools->checkIpRestrictedAccess($request)) {
            throw new NotFoundHttpException();
        }

        $license = $this->licenseRepository->findActiveLicense($licenseHolder);
        if (!$license) {
            return $this->json([
                'license' => [
                    'isValid' => false,
                    'dateValid' => '0000-00-00',
                ],
                'message' => 'license not found'
            ]);
        }

        $futureLicences = $this->licenseRepository->findFutureLicenses($licenseHolder, $license->getId());
        $futureLicenseData = [];
        foreach ($futureLicences as $futureLicence) {
            $licenseProductItem = $this->itemRepository->findById($futureLicence->getProduct()->getItem());
            $licenseProductItemData = $licenseProductItem->getData();

            $futureLicenseData[] = [
                'dateValid' => $futureLicence->getDateValid()->format('d.m.Y'),
                'dateStart' => $futureLicence->getDateStart()->format('d.m.Y'),
                'product' => [
                    'name' => $licenseProductItemData['name']
                ]
            ];
        }

        $archivedLicences = $this->licenseRepository->findArchivedLicenses($licenseHolder);
        $archivedLicenseData = [];
        foreach ($archivedLicences as $archivedLicence) {
            $licenseProductItem = $this->itemRepository->findById($archivedLicence->getProduct()->getItem());
            $licenseProductItemData = $licenseProductItem->getData();

            $archivedLicenseData[] = [
                'dateValid' => $archivedLicence->getDateValid()->format('d.m.Y'),
                'dateStart' => $archivedLicence->getDateStart()->format('d.m.Y'),
                'product' => [
                    'name' => $licenseProductItemData['name']
                ]
            ];
        }

        if (!$license->isValid()) {
            return $this->json([
                'license' => [
                    'isValid' => false,
                    'dateValid' => $license->getDateValid()
                        ? $license->getDateValid()->format('Y-m-d H:i:s')
                        : 'lifetime',
                    'isTrial' => $license->getComment() === 'trial'
                ],
                'message' => $this->translator->trans('license.messages.expired'),
            ]);
        }

        $licenseProductItem = $this->itemRepository->findById($license->getProduct()->getItem());
        $licenseProductItemData = $licenseProductItem->getData();

        return $this->json([
            'license' => [
                'isValid' => true,
                'dateValid' => $license->getDateValid()
                    ? $license->getDateValid()->format('Y-m-d H:i:s')
                    : 'lifetime',
                'isTrial' => $license->getComment() === 'trial',
                'product' => [
                    'name' => $licenseProductItemData['name']
                ]
            ],
            'future' => $futureLicenseData,
            'archive' => $archivedLicenseData,
            'message' => '',
        ]);
    }


    #[Route('/api/license/{_locale}/list', name: 'license_list_remote', methods: ['GET'])]
    #[Route('/license/{_locale}/list/{licenseHolder}', name: 'license_list_local', methods: ['GET'])]
    public function list(
        Request $request,
        ?string $licenseHolder,
    ): Response {
        $self = ($request->server->getBoolean('HTTPS') ? 'https://' : 'http://')
            . $request->server->get('HTTP_HOST');

        if (
            // if we are on the license server and test mode is not enabled, no need to check for a license
            $this->getParameter('license.server') !== $self
        ) {
            // get license products from license server
            return $this->json(json_decode($this->httpClient->request(
                'GET',
                $this->getParameter('license.server')
                . '/license/de/list/'
                . $this->getParameter('license.holder')
            )->getContent()));
        }

        // access to license server is only allowed from instance servers
        if (!$this->securityTools->checkIpRestrictedAccess($request)) {
            throw new NotFoundHttpException();
        }

        $availableLicenses = $this->licenseProductRepository->findPurchasableLicenseProducts();

        return $this->json([
            'products' => $availableLicenses
        ]);
    }

    #[Route('/api/license/{_locale}/buy/{productId}', name: 'license_buy', methods: ['GET'])]
    public function buy(
        Request $request,
        int $productId,
    ): Response {
        // get license products from license server
        $response = $this->httpClient->request(
            'GET',
            $this->getParameter('license.server')
            . '/license/purchase-link/'
            . $productId . '/'
            . $this->getParameter('license.holder')
        );

        return $this->json([
            'url' => $response->toArray()['url'],
            'product' => $response->toArray()['product'],
        ]);
    }

    #[Route('/license/success/{hash}/{checkoutSessionId}', name: 'license_buy_success', methods: ['GET'])]
    public function success(
        string $hash,
        string $checkoutSessionId,
        ItemRepository $itemRepository,
        ItemUnitRepository $itemUnitRepository,
    ) : Response {
        $purchase = $this->licensePurchaseRepository->findOneBy(['hash' => $hash]);

        if ($purchase->getCheckoutId()) {
            return new Response($this->translator->trans('license.subscription.alreadyProcessed'));
        }

        $licenseItem = $itemRepository->findById($purchase->getProduct()->getItem());
        $itemUnit = $itemUnitRepository->find($licenseItem->getSelectField('unit_id')['id']);

        $purchase->setCheckoutId($checkoutSessionId);
        $purchase->setDateCompleted(new DateTimeImmutable());

        $oldLicense = $this->licenseRepository->findOneBy(['holder' => $purchase->getHolder()],['dateValid' => 'DESC']);
        $licenseStartDate = new DateTime();
        if ($oldLicense) {
            // if there is an existing license, start new license start date after
            if ($oldLicense->getDateValid()->getTimestamp() > (new DateTime())->getTimestamp()) {
                $licenseStartDate->setTimestamp($oldLicense->getDateValid()->getTimestamp());
            }
        }

        $license = new License();
        $license->setHolder($purchase->getHolder());
        $license->setProduct($purchase->getProduct());
        $license->setContact($purchase->getContact());
        $license->setDateCreated(new DateTimeImmutable());
        $license->setDateStart($licenseStartDate);
        if ($itemUnit->getType() === 'sub-month') {
            $licenseValidDate = clone $licenseStartDate;
            $licenseValidDate->modify('+30 days');
            $license->setDateValid($licenseValidDate);
        }
        if ($itemUnit->getType() === 'sub-year') {
            $licenseValidDate = clone $licenseStartDate;
            $licenseValidDate->modify('+365 days');
            $license->setDateValid($licenseValidDate);
        }
        // todo: remove these fields if not really needed
        $license->setUrlApi('');
        $license->setUrlClient('');

        $this->licenseRepository->save($license, true);

        return new Response($this->translator->trans('license.subscription.paymentSuccess'));
    }

    #[Route('/license/purchase-link/{id}/{licenseHolder}', name: 'license_purchase_get_checkout_url', methods: ['GET'])]
    public function getLicensePurchaseCheckoutUrl(
        Request $request,
        LicenseProduct $licenseProduct,
        ItemRepository $itemRepository,
        ItemUnitRepository $itemUnitRepository,
        string $licenseHolder,
    ): Response {
        // make sure we are on the license server instance
        $self = ($request->server->getBoolean('HTTPS') ? 'https://' : 'http://')
            . $request->server->get('HTTP_HOST');

        if ($this->getParameter('license.server') != $self) {
            throw new NotFoundHttpException();
        }

        // access to license server is only allowed from instance servers
        if (!$this->securityTools->checkIpRestrictedAccess($request)) {
            throw new NotFoundHttpException();
        }

        Stripe::setApiKey($this->getParameter('payment.stripe_secret'));

        try {
            $licenseItem = $itemRepository->findById($licenseProduct->getItem());
            if (!$licenseItem) {
                return $this->json(['error' => 'invalid license item']);
            }
            $price = Price::retrieve($licenseItem->getTextField('stripe_price_id'));
            $hash = Uuid::v4();
            $contact = $this->contactRepository->findByAttribute('contact_identifier', $licenseHolder);
            if (!$contact) {
                return $this->json(['error' => 'contact not found']);
            }

            $purchase = new LicensePurchase();
            $purchase->setContact($contact->getId());
            $purchase->setHolder($licenseHolder);
            $purchase->setHash($hash);
            $purchase->setProduct($licenseProduct);
            $purchase->setDateCreated(new DateTimeImmutable());

            $this->licensePurchaseRepository->save($purchase, true);

            $sessionData = [
                'line_items' => [[
                    'price' => $price->id,
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $self . '/license/success/' . $hash . '/{CHECKOUT_SESSION_ID}',
                'cancel_url' => $self . '/license/cancel',
            ];

            $itemUnit = $itemUnitRepository->find($licenseItem->getTextField('unit_id'));

            if (
                $itemUnit?->getType() === 'sub-month'
                || $itemUnit?->getType() === 'sub-year'
            ) {
                $sessionData['mode'] = 'subscription';
            }

            $checkout_session = Session::create($sessionData);

            $itemData = $licenseItem->getData();

            return $this->json([
                'url' => $checkout_session->url,
                'product' => [
                    'id' => $licenseProduct->getId(),
                    'item' =>  [
                        'id' => $itemData['id'],
                        'name' => $itemData['name'],
                        'price' => $itemData['price'],
                        'description' => $itemData['description'],
                        'unit' => $licenseItem->getSelectField('unit_id'),
                    ]
                ],
            ]);
        } catch (Error $e) {
            return $this->json(['error' => $e->getMessage()]);
        }
    }

    #[Route('/api/license/{_locale}/notifications', name: 'license_notifications_remote', methods: ['GET'])]
    #[Route('/license/{_locale}/notifications/{licenseHolder}', name: 'license_notifications_local', methods: ['GET'])]
    public function notifications(
        Request $request,
        LicenseClientNotificationRepository $clientNotificationRepository,
        ?string $licenseHolder,
    ): Response
    {
        $self = ($request->server->getBoolean('HTTPS') ? 'https://' : 'http://')
            . $request->server->get('HTTP_HOST');

        if (
            // if we are not on license server, redirect its response
            $this->getParameter('license.server') !== $self
        ) {
            // get license products from license server
            return $this->json(json_decode($this->httpClient->request(
                'GET',
                $this->getParameter('license.server')
                . '/license/de/notifications/'
                . $this->getParameter('license.holder')
            )->getContent()));
        }

        // access to license server is only allowed from instance servers
        if (!$this->securityTools->checkIpRestrictedAccess($request)) {
            throw new NotFoundHttpException();
        }

        $myNotifications = $clientNotificationRepository->getOpenNotifications($licenseHolder);

        return $this->json([
            'notifications' => $myNotifications
        ]);
    }
}