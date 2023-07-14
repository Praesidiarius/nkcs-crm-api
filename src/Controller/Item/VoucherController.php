<?php

namespace App\Controller\Item;

use App\Controller\AbstractDynamicFormController;
use App\Form\DynamicType;
use App\Form\Item\VoucherType;
use App\Model\DynamicDto;
use App\Repository\AbstractRepository;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\UserSettingRepository;
use App\Repository\VoucherRepository;
use Doctrine\DBAL\Connection;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/voucher/{_locale}')]
class VoucherController extends AbstractDynamicFormController
{
    public function __construct(
        private readonly VoucherType $voucherForm,
        private readonly VoucherRepository $voucherRepository,
        private readonly UserSettingRepository $userSettings,
        private readonly HttpClientInterface $httpClient,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly Connection $connection,
    ) {
        parent::__construct(
            $this->httpClient,
            $this->userSettings,
            $this->dynamicFormFieldRepository,
            $this->connection,
        );
    }

    #[Route('/add', name: 'voucher_add', methods: ['GET'])]
    public function getAddForm($form = null, $formKey = 'voucher'): Response {
        return parent::getAddForm($this->voucherForm, 'voucher');
    }

    #[Route('/add', name: 'voucher_add_save', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        $form = $this->createForm(VoucherType::class);
        $form->submit($data);

        if (!$form->isValid()) {
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        $voucher = new DynamicDto($this->dynamicFormFieldRepository, $this->connection);
        $voucher->setData($data);

        // manual validation
        if ($voucher->getTextField('name') === null) {
            return $this->json([
                ['message' => 'You must provide a name']
            ], 400);
        }

        // set readonly fields
        $voucher->setCreatedBy($this->getUser()->getId());
        $voucher->setCreatedDate();

        // save contact
        $voucher = $this->voucherRepository->save($voucher);

        /**
         * Stripe Integration
         */
        if ($this->getParameter('payment.stripe_key') && $voucher->getBoolField('stripe_enabled')) {
            $stripeClient = new StripeClient($this->getParameter('payment.stripe_secret'));

            $stripeProduct = $stripeClient->products->create([
                'name' => $voucher->getTextField('name'),
                'description' => $voucher->getTextField('description'),
            ]);

            $stripePriceData = [
                // stripe wants amount in cents
                'unit_amount' => ($voucher->getPriceField('price') ?? 0) * 100,
                'currency' => $this->getParameter('payment.currency'),
                'product' => $stripeProduct['id']
            ];

            if ($voucher->getSelectField('unit_id')['type'] === 'sub-month') {
                $stripePriceData['recurring'] = ['interval' => 'month'];
            }
            if ($voucher->getSelectField('unit_id')['type'] === 'sub-year') {
                $stripePriceData['recurring'] = ['interval' => 'year'];
            }
            $stripePrice = $stripeClient->prices->create($stripePriceData);

            $voucher->setTextField('stripe_price_id', $stripePrice['id']);

            $voucher = $this->voucherRepository->save($voucher);
        }

        return $this->itemResponse($voucher);
    }

    #[Route('/edit/{voucherId}', name: 'voucher_edit', methods: ['GET'])]
    public function getEditForm(int $voucherId): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $voucher = $this->voucherRepository->findById($voucherId);

        return $this->itemResponse($voucher);
    }

    #[Route('/edit/{id}', name: 'voucher_edit_save', methods: ['POST'])]
    public function saveEditForm(
        Request $request,
        int $voucherId,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        // unset readonly fields
        unset($data['createdBy']);
        unset($data['createdDate']);
        unset($data['id']);

        $form = $this->createForm(VoucherType::class);
        $form->submit($data);

        if (!$form->isValid()) {
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        $voucher = new DynamicDto($this->dynamicFormFieldRepository, $this->connection);

        $voucher->setData($data);
        $voucher->setId($voucherId);

        $voucher = $this->voucherRepository->save($voucher);

        return $this->itemResponse($voucher);
    }

    #[Route('/view/{entityId}', name: 'voucher_view', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function view(
        int $entityId,
        ?AbstractRepository $repository = null,
        string $formKey = 'voucher',
        ?DynamicType $form = null,
    ): Response {
        $voucher = $this->voucherRepository->findById($entityId);
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->itemResponse($voucher, 'voucher', $this->voucherForm);
    }

    #[Route('/remove/{voucherId}', name: 'voucher_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function delete(
        int $voucherId,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        if ($voucherId > 0) {
            $this->voucherRepository->removeById($voucherId);
        }

        return $this->json(['state' => 'success']);
    }

    #[Route('/list', name: 'voucher_index', methods: ['GET'])]
    #[Route('/list/{page}', name: 'voucher_index_with_pagination', methods: ['GET'])]
    public function list(
        ?int $page,
        ?AbstractRepository $repository = null,
        ?DynamicType $form = null,
        string $formKey = '',
    ): Response {
        return parent::list($page, $this->voucherRepository, $this->voucherForm, 'voucher');
    }

    protected function itemResponse(
        ?DynamicDto $dto,
        string $formKey = 'voucher',
        ?DynamicType $form = null,
        array $extraData = [],
    ): Response {
        return parent::itemResponse(
            $dto,
            'voucher',
            $this->voucherForm,
            $extraData,
        );
    }
}