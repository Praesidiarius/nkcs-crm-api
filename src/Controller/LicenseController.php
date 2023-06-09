<?php

namespace App\Controller;

use App\Entity\License;
use App\Entity\LicenseProduct;
use App\Entity\LicensePurchase;
use App\Repository\ContactRepository;
use App\Repository\LicenseProductRepository;
use App\Repository\LicensePurchaseRepository;
use App\Repository\LicenseRepository;
use DateTimeImmutable;
use Error;
use Stripe\Checkout\Session;
use Stripe\Price;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/license')]
class LicenseController extends AbstractController
{
    public function __construct(
        private readonly LicenseRepository $licenseRepository,
        private readonly LicenseProductRepository $licenseProductRepository,
        private readonly LicensePurchaseRepository $licensePurchaseRepository,
        private readonly ContactRepository $contactRepository,
        private readonly TranslatorInterface $translator,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    #[Route('/check/{licenseHolder}', name: 'license_check', methods: ['GET'])]
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


    #[Route('/{_locale}/list', name: 'license_list_remote', methods: ['GET'])]
    #[Route('/{_locale}/list/{licenseHolder}', name: 'license_list_local', methods: ['GET'])]
    public function list(
        Request $request,
        ?string $licenseHolder,
    ): ResponseInterface|JsonResponse {
        $self = ($request->server->getBoolean('HTTPS') ? 'https://' : 'http://')
            . $request->server->get('HTTP_HOST');

        if (
            // if we are on the license server and test mode is not enabled, no need to check for a license
            $this->getParameter('license.server') !== $self
        ) {
            // get license products from license server
            return $this->httpClient->request(
                'GET',
                $this->getParameter('license.server')
                . '/license/list/'
                . $this->getParameter('license.holder')
            );
        }

        $availableLicenses = $this->licenseProductRepository->findPurchasableLicenseProducts();

        return $this->json([
            'products' => $availableLicenses
        ]);
    }

    #[Route('/{_locale}/buy/{id}', name: 'license_buy', methods: ['GET'])]
    public function buy(
        Request $request,
        LicenseProduct $licenseProduct,
    ): Response {
        // get license products from license server
        $response = $this->httpClient->request(
            'GET',
            $this->getParameter('license.server')
            . '/license/purchase-link/'
            . $licenseProduct->getId() . '/'
            . $this->getParameter('license.holder')
        );

        return $this->json([
            'url' => $response->toArray()['url']
        ]);
    }

    #[Route('/success/{hash}/{checkoutSessionId}', name: 'license_buy_success', methods: ['GET'])]
    public function success(
        string $hash,
        string $checkoutSessionId,
    ) : Response {
        $purchase = $this->licensePurchaseRepository->findOneBy(['hash' => $hash]);

        $purchase->setCheckoutId($checkoutSessionId);
        $purchase->setDateCompleted(new DateTimeImmutable());

        $license = new License();
        $license->setHolder($purchase->getHolder());
        $license->setProduct($purchase->getProduct());
        $license->setContact($purchase->getContact());
        $license->setDateCreated(new DateTimeImmutable());
        $license->setDateValid(new DateTimeImmutable(date('Y-m-d H:i:s', strtotime('+30 days'))));
        // todo: fix this
        $license->setUrlApi('test');
        $license->setUrlClient('test');

        $this->licenseRepository->save($license, true);

        // todo: redirect back to client
        return new Response('Done');
    }

    #[Route('/purchase-link/{id}/{licenseHolder}', name: 'license_purchase_get_checkout_url', methods: ['GET'])]
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
            // todo: move price to item
            $price = Price::retrieve('TEST');

            $factory = new PasswordHasherFactory([
                'common' => ['algorithm' => 'bcrypt'],
            ]);

            $hasher = $factory->getPasswordHasher('common');
            $hash = Uuid::v4();

            // fix this
            $demoContact = $this->contactRepository->find(1);

            $purchase = new LicensePurchase();
            $purchase->setContact($demoContact);
            $purchase->setHolder($licenseHolder);
            $purchase->setHash($hash);
            $purchase->setProduct($licenseProduct);
            $purchase->setDateCreated(new DateTimeImmutable());

            $this->licensePurchaseRepository->save($purchase, true);

            $checkout_session = Session::create([
                'line_items' => [[
                    'price' => $price->id,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $self . '/license/success/' . $hash . '/{CHECKOUT_SESSION_ID}',
                'cancel_url' => $self . '/license/cancel',
            ]);

            return $this->json([
                'url' => $checkout_session->url,
            ]);
        } catch (Error $e) {
            return $this->json(['error' => $e->getMessage()]);
        }
    }
}