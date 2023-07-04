<?php

namespace App\Controller;

use App\Entity\License;
use App\Entity\LicenseProduct;
use App\Entity\LicensePurchase;
use App\Repository\LegacyContactRepository;
use App\Repository\LicenseProductRepository;
use App\Repository\LicensePurchaseRepository;
use App\Repository\LicenseRepository;
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
        private readonly LicenseRepository         $licenseRepository,
        private readonly LicenseProductRepository  $licenseProductRepository,
        private readonly LicensePurchaseRepository $licensePurchaseRepository,
        private readonly LegacyContactRepository   $contactRepository,
        private readonly TranslatorInterface       $translator,
        private readonly HttpClientInterface       $httpClient,
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

        $license = $this->licenseRepository->findOneBy(['holder' => $licenseHolder]);
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
                        : 'lifetime'
                ],
                'message' => $this->translator->trans('license.messages.expired'),
            ]);
        }

        return $this->json([
            'license' => [
                'isValid' => true,
                'dateValid' => $license->getDateValid()
                    ? $license->getDateValid()->format('Y-m-d H:i:s')
                    : 'lifetime'
            ],
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
    ) : Response {
        $purchase = $this->licensePurchaseRepository->findOneBy(['hash' => $hash]);

        if ($purchase->getCheckoutId()) {
            return new Response($this->translator->trans('license.subscription.alreadyProcessed'));
        }

        $purchase->setCheckoutId($checkoutSessionId);
        $purchase->setDateCompleted(new DateTimeImmutable());

        $license = new License();
        $license->setHolder($purchase->getHolder());
        $license->setProduct($purchase->getProduct());
        $license->setContact($purchase->getContact());
        $license->setDateCreated(new DateTimeImmutable());
        if ($license->getProduct()->getItem()->getUnit()->getType() === 'sub-month') {
            $license->setDateValid(new DateTimeImmutable(date('Y-m-d H:i:s', strtotime('+30 days'))));
        }
        if ($license->getProduct()->getItem()->getUnit()->getType() === 'sub-year') {
            $license->setDateValid(new DateTimeImmutable(date('Y-m-d H:i:s', strtotime('+365 days'))));
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
        string $licenseHolder,
    ): Response {
        // make sure we are on the license server instance
        $self = ($request->server->getBoolean('HTTPS') ? 'https://' : 'http://')
            . $request->server->get('HTTP_HOST');

        if ($this->getParameter('license.server') != $self) {
            throw new NotFoundHttpException();
        }

        Stripe::setApiKey($this->getParameter('payment.stripe_secret'));

        try {
            if (!$licenseProduct->getItem()?->getStripePriceId()) {
                return $this->json(['error' => 'invalid license item']);
            }
            $price = Price::retrieve($licenseProduct->getItem()->getStripePriceId());
            $hash = Uuid::v4();
            $contact = $this->contactRepository->findOneBy(['contactIdentifier' => $licenseHolder]);
            if (!$contact) {
                return $this->json(['error' => 'contact not found']);
            }

            $purchase = new LicensePurchase();
            $purchase->setContact($contact);
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

            if (
                $licenseProduct->getItem()->getUnit()->getType() === 'sub-month'
                || $licenseProduct->getItem()->getUnit()->getType() === 'sub-year'
            ) {
                $sessionData['mode'] = 'subscription';
            }

            $checkout_session = Session::create($sessionData);

            return $this->json([
                'url' => $checkout_session->url,
                'product' => $licenseProduct,
            ]);
        } catch (Error $e) {
            return $this->json(['error' => $e->getMessage()]);
        }
    }
}