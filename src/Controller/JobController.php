<?php

namespace App\Controller;

use App\Entity\ItemVoucherCode;
use App\Entity\ItemVoucherCodeRedeem;
use App\Entity\JobPosition;
use App\Enum\ItemVoucherType;
use App\Enum\JobVatMode;
use App\Form\DynamicType;
use App\Form\Job\JobType;
use App\Model\DynamicDto;
use App\Repository\AbstractRepository;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\ItemRepository;
use App\Repository\ItemTypeRepository;
use App\Repository\ItemUnitRepository;
use App\Repository\ItemVoucherCodeRedeemRepository;
use App\Repository\ItemVoucherCodeRepository;
use App\Repository\JobPositionRepository;
use App\Repository\JobRepository;
use App\Repository\UserSettingRepository;
use App\Repository\VoucherRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/job/{_locale}')]
class JobController extends AbstractDynamicFormController
{
    public function __construct(
        private readonly JobType $jobForm,
        private readonly JobRepository $jobRepository,
        private readonly JobPositionRepository $jobPositionRepository,
        private readonly UserSettingRepository $userSettings,
        private readonly ItemRepository $itemRepository,
        private readonly ItemUnitRepository $itemUnitRepository,
        private readonly VoucherRepository $voucherRepository,
        private readonly ItemVoucherCodeRepository $voucherCodeRepository,
        private readonly ItemVoucherCodeRedeemRepository $voucherCodeRedeemRepository,
        private readonly ItemTypeRepository $itemTypeRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly TranslatorInterface $translator,
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

    #[Route('/add', name: 'job_add', methods: ['GET'])]
    public function getAddForm($form = null, $formKey = 'job'): Response {
        return parent::getAddForm($this->jobForm, 'jobType1');
    }

    #[Route('/add', name: 'job_add_save', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        $form = $this->createForm(JobType::class);
        $form->submit($data);

        if (!$form->isValid()) {
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        // manual validation
        /**
        if ($job->getTitle() === null) {
            return $this->json([
                ['message' => 'You must provide a title']
            ], 400);
        } **/

        $job = $this->jobRepository->getDynamicDto();
        $job->setData($data);

        $job->setSelectField('type_id', 1);

        // set readonly fields
        $job->setCreatedBy($this->getUser()->getId());
        $job->setCreatedDate();

        // save job
        $this->jobRepository->save($job);

        return $this->itemResponse($job);
    }

    #[Route('/edit/{jobId}', name: 'job_edit', methods: ['GET'])]
    public function getEditForm(int $jobId): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $job = $this->jobRepository->findById($jobId);

        return $this->itemResponse($job);
    }

    #[Route('/edit/{jobId}', name: 'job_edit_save', methods: ['POST'])]
    public function saveEditForm(
        Request $request,
        int $jobId,
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

        $form = $this->createForm(JobType::class);
        $form->submit($data);

        if (!$form->isValid()) {
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        $job = $this->jobRepository->getDynamicDto();
        $job->setData($data);
        $job->setId($jobId);

        $this->jobRepository->save($job);

        return $this->itemResponse($job);
    }

    #[Route('/view/{entityId}', name: 'job_view', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function view(
        int $entityId,
        ?AbstractRepository $repository = null,
        string $formKey = 'item',
        ?DynamicType $form = null,
    ): Response {
        $job = $this->jobRepository->findById($entityId);
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $extraData = [
            'positions' => $job->getJobPositions(),
            'position_units' => $this->listUnits(),
            'finance' => [
                'sub_total' => $job->getPriceField('sub_total'),
                'sub_total_text' => $job->getPriceFieldText('sub_total'),
                'vat_total' => $job->getPriceField('vat_total'),
                'vat_total_text' => $job->getPriceFieldText('vat_total'),
                'total' => $job->getPriceField('total'),
                'total_text' => $job->getPriceFieldText('total'),
            ],
            'vouchers_used' => $job->getVouchersUsed(),
        ];

        return $this->itemResponse($job, 'job', $this->jobForm, $extraData);
    }

    #[Route('/voucher/redeem', name: 'job_voucher_code_redeem', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    public function redeemVoucherCode(
        Request $request,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        $jobId = (int) $data['job_id'];
        if ($jobId <= 0) {
            throw new HttpException(404, 'job not found');
        }
        $job = $this->jobRepository->findById($jobId);

        if (count($job->getJobPositions()) === 0) {
            throw new HttpException(400, 'you cannot redeem a voucher in an empty job. add positions first.');
        }

        $voucherCodeUuid = $data['voucher_code'];
        if (strlen($voucherCodeUuid) < 6) {
            throw new HttpException(400, 'invalid voucher code');
        }
        $voucherCode = $this->voucherCodeRepository->findOneBy(['code' => $voucherCodeUuid]);
        if (!$voucherCode) {
            throw new HttpException(404, 'voucher code not found');
        }

        $voucherCodeUsages = $this->voucherCodeRedeemRepository->findBy(['voucherCodeId' => $voucherCode->getId()]);
        // voucher codes linked to an item, are gift cards and can only be used once
        if ($voucherCode->getItemId() && $voucherCodeUsages) {
            throw new HttpException(400, 'voucher already used');
        }

        $voucher = null;
        if ($voucherCode->getVoucherId()) {
            $voucher = $this->voucherRepository->findById($voucherCode->getVoucherId());
            if ($voucher->getIntField('contact_id')) {
                if ($voucher->getIntField('contact_id') !== $job->getIntField('contact_id')) {
                    throw new HttpException(400, 'voucher cannot be used for this customer');
                }
            }
            if ($voucher->getIntField('use_only_once')) {
                foreach ($voucherCodeUsages as $usage) {
                    if (
                        $usage->getContactId() === $voucher->getIntField('contact_id')
                        || $usage->getContactId() === $job->getIntField('contact_id')
                    ) {
                        throw new HttpException(400, 'voucher already used');
                    }
                }
            }
        }

        $voucherRedeem = new ItemVoucherCodeRedeem();
        $voucherRedeem->setVoucherCodeId($voucherCode->getId());
        $voucherRedeem->setJobId($job->getId());
        $voucherRedeem->setContactId($job->getIntField('contact_id'));
        $voucherRedeem->setDate();

        $this->voucherCodeRedeemRepository->save($voucherRedeem, true);

        // check if voucher is a gift card
        if ($voucherCode->getItemId()) {
            // apply voucher to job total
            $voucherItem = $this->itemRepository->findById($voucherCode->getItemId());
            $voucherDiscount = $voucherItem->getPriceField('price');
            $currentJobTotal = $job->getPriceField('total');
            $newJobTotal = $currentJobTotal - $voucherDiscount;
            $job->setPriceField('total', $newJobTotal);

            $this->jobRepository->updateAttribute('total', $newJobTotal, $job->getId());
        }

        if ($voucher) {
            $voucherDiscount = $voucher->getPriceField('amount');
            $voucherType = ItemVoucherType::from($voucher->getTextField('voucher_type'));
            $currentJobTotal = $job->getPriceField('total');

            $newJobTotal = match($voucherType) {
                ItemVoucherType::ABSOLUTE => $currentJobTotal - $voucherDiscount,
                ItemVoucherType::PERCENT => $currentJobTotal * ((100 - $voucherDiscount) / 100)
            };

            $job->setPriceField('total', $newJobTotal);
            $this->jobRepository->updateAttribute('total', $newJobTotal, $job->getId());
        }

        return $this->json([
            'state' => 'success',
            'vouchers_used' => $job->getVouchersUsed(),
            'job' => [
                'total' => $job->getPriceField('total'),
                'total_text' => $job->getPriceFieldText('total'),
            ]
        ]);
    }

    #[Route('/remove/{jobId}', name: 'job_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function delete(
        int $jobId,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        if ($jobId > 0) {
            $this->jobRepository->removeById($jobId);
        }

        return $this->json(['state' => 'success']);
    }

    #[Route('/position/remove/{positionId}', name: 'job_position_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function deletePosition(
        int $positionId,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        if ($positionId > 0) {
            $position = $this->jobPositionRepository->find($positionId);
            if (!$position) {
                throw new HttpException(404, 'position not found');
            }
            $job = $this->jobRepository->findById($position->getJobId());

            $posItem = $position->getItemId() ? $this->itemRepository->findById($position->getItemId()) : false;
            if ($posItem) {
                $posItemTypeId = $posItem->getIntField('type_id');
                if ($posItemTypeId !== 1) {
                    $posItemType = $this->itemTypeRepository->find($posItemTypeId);

                    switch ($posItemType->getType()) {
                        case 'giftcard':
                            $codes = $this->voucherCodeRepository->findBy(['position' => $position]);
                            foreach ($codes as $code) {
                                $this->voucherCodeRepository->remove($code, true);
                            }
                            break;
                        default:
                            break;
                    }
                }
            }

            $this->jobPositionRepository->remove($position, true);

            $job = $this->recalcJobFinances($job);

            return $this->json([
                'positions' => $job->getJobPositions(),
                'finance' => [
                    'sub_total' => $job->getPriceField('sub_total'),
                    'sub_total_text' => $job->getPriceFieldText('sub_total'),
                    'vat_total' => $job->getPriceField('vat_total'),
                    'vat_total_text' => $job->getPriceFieldText('vat_total'),
                    'total' => $job->getPriceField('total'),
                    'total_text' => $job->getPriceFieldText('total'),
                ],
                'state' => 'success'
            ]);
        }

        throw new HttpException(404, 'position not found');
    }

    #[Route('/list', name: 'job_index', methods: ['GET'])]
    #[Route('/list/{page}', name: 'job_index_with_pagination', methods: ['GET'])]
    public function list(
        ?int $page,
        ?AbstractRepository $repository = null,
        ?DynamicType $form = null,
        string $formKey = '',
    ): Response {
        return parent::list($page, $this->jobRepository, $this->jobForm, 'jobType1');
    }

    #[Route('/position', name: 'job_position_add', methods: ['POST'])]
    public function savePositionAddForm(
        Request $request,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        $position = new JobPosition();

        $jobId = (int) $data['job'];
        $unitId = (int) $data['unit'];

        if ($jobId <= 0) {
            return $this->json([
                ['message' => 'Invalid Job ID']
            ], 400);
        }

        $job = $this->jobRepository->findById($jobId);

        $position->setJobId($jobId);
        $position->setComment($data['title']);
        $position->setAmount($data['amount']);

        $itemId = (int) $data['item'];
        $posItem = null;
        if ($itemId > 0) {
            $item = $this->itemRepository->findById($itemId);
            if (!$item) {
                return $this->json([
                    ['message' => 'item not found']
                ], 404);
            }
            $posItem = $item;
            $unit = $this->itemUnitRepository->find($item->getIntField('unit_id'));
            $position->setItemId($itemId);
            $position->setUnit($unit);
        } else {
            if ($unitId <= 0) {
                return $this->json([
                    ['message' => 'Invalid Unit']
                ], 400);
            }
            $unit = $this->itemUnitRepository->find($unitId);
            $position->setUnit($unit);
            $position->setPrice($data['price']);
        }

        // save position
        $position = $this->jobPositionRepository->save($position, true);

        if ($posItem) {
            $posItem->serializeDataForApiByFormModel('item');

            // item type handling
            $itemType = $posItem->getSelectField('type_id');
            if (array_key_exists('type', $itemType)) {
                // if item is a giftcard, lets create voucher codes
                if ($itemType['type'] === 'giftcard') {
                    for ($i = 0; $i < $position->getAmount(); $i++) {
                        $voucherCode = new ItemVoucherCode();
                        $voucherCode->setItemId($posItem->getId());
                        $voucherCode->generateCode();
                        $voucherCode->setJobPosition($position);

                        $this->voucherCodeRepository->save($voucherCode, true);
                    }
                }
            }
        }

        $jobPositionsNew = $job->getJobPositions();
        $job = $this->recalcJobFinances($job);

        return $this->json([
            'positions' => $jobPositionsNew,
            'finance' => [
                'sub_total' => $job->getPriceField('sub_total'),
                'sub_total_text' => $job->getPriceFieldText('sub_total'),
                'vat_total' => $job->getPriceField('vat_total'),
                'vat_total_text' => $job->getPriceFieldText('vat_total'),
                'total' => $job->getPriceField('total'),
                'total_text' => $job->getPriceFieldText('total'),
            ],
        ]);
    }

    private function recalcJobFinances(DynamicDto $job): DynamicDto
    {
        $positions = $job->getJobPositions();
        $jobSubTotal = 0;
        foreach ($positions as $jobPosition) {
            $jobSubTotal += $jobPosition->getTotal();
        }
        $job->setPriceField('sub_total',$jobSubTotal);
        if ($job->getIntField('vat_mode') !== JobVatMode::VAT_NONE->value) {
            $job->setPriceField('vat_rate', $this->getParameter('job.vat_rate_default'));
            $jobVat = $jobSubTotal * ($this->getParameter('job.vat_rate_default') / 100);
            $job->setPriceField('vat_total', $jobVat);
            $jobVouchers = $job->getVouchersUsed();
            foreach ($jobVouchers as $voucher) {
                $jobSubTotal = match($voucher['type']) {
                    ItemVoucherType::ABSOLUTE => $jobSubTotal - $voucher['amount'],
                    ItemVoucherType::PERCENT => $jobSubTotal * ((100 - $voucher['amount']) / 100)
                };
            }
            $job->setPriceField('total', $jobSubTotal + $jobVat);
        } else {
            $jobVouchers = $job->getVouchersUsed();
            foreach ($jobVouchers as $voucher) {
                $jobSubTotal = match($voucher['type']) {
                    ItemVoucherType::ABSOLUTE => $jobSubTotal - $voucher['amount'],
                    ItemVoucherType::PERCENT => $jobSubTotal * ((100 - $voucher['amount']) / 100)
                };
            }
            $job->setPriceField('total', $jobSubTotal);
        }
        return $this->jobRepository->save($job);
    }


    private function listUnits(): array
    {
        $units = $this->itemUnitRepository->findAll();
        $unitsTranslated = [];

        foreach ($units as $unit) {
            $unitsTranslated[] = [
                'id' => $unit->getId(),
                'text' => $this->translator->trans($unit->getName()),
            ];
        }

        return $unitsTranslated;
    }

    protected function itemResponse(
        ?DynamicDto $dto,
        string $formKey = 'jobType1',
        ?DynamicType $form = null,
        array $extraData = []
    ): Response {
        return parent::itemResponse(
            $dto,
            'jobType1',
            $this->jobForm,
            $extraData
        );
    }
}