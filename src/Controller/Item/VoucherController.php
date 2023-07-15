<?php

namespace App\Controller\Item;

use App\Controller\AbstractDynamicFormController;
use App\Entity\ItemVoucherCode;
use App\Form\DynamicType;
use App\Form\Item\VoucherType;
use App\Model\DynamicDto;
use App\Repository\AbstractRepository;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\ItemVoucherCodeRepository;
use App\Repository\UserSettingRepository;
use App\Repository\VoucherRepository;
use Doctrine\DBAL\Connection;
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
        private readonly ItemVoucherCodeRepository $voucherCodeRepository,
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

        $voucherCodeCustom = $data['code'];
        unset($data['code']);

        // check if code is already taken
        $codeExists = $this->voucherCodeRepository->findBy(['code' => $voucherCodeCustom]);
        if ($codeExists) {
            throw new HttpException(400, 'code already taken. please choose another one');
        }

        $voucher = $this->voucherRepository->getDynamicDto();
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

        $voucher = $this->voucherRepository->save($voucher);

        // create voucher code
        $voucherCode = new ItemVoucherCode();
        $voucherCode->setVoucherId($voucher->getId());
        $voucherCode->setCode($voucherCodeCustom);

        $this->voucherCodeRepository->save($voucherCode, true);

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

        $voucherCode = $this->voucherCodeRepository->findOneBy(['voucherId' => $voucher->getId()]);
        if ($voucherCode) {
            $voucher->setTextField('code', $voucherCode->getCode());
        }

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