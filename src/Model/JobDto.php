<?php

namespace App\Model;

use App\Entity\DynamicFormField;
use App\Entity\ItemVoucherCode;
use App\Enum\ItemVoucherType;
use App\Enum\JobVatMode;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\ItemRepository;
use App\Repository\ItemTypeRepository;
use App\Repository\ItemVoucherCodeRepository;
use App\Repository\JobPositionRepository;
use App\Repository\VoucherRepository;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

class JobDto extends DynamicDto
{
    public function __construct(
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
        private readonly JobPositionRepository $jobPositionRepository,
        private readonly ItemTypeRepository $itemTypeRepository,
        private readonly ItemRepository $itemRepository,
        private readonly ItemVoucherCodeRepository $voucherCodeRepository,
        private readonly VoucherRepository $voucherRepository,
    ) {
        parent::__construct($this->dynamicFormFieldRepository, $this->connection);
    }

    protected function getSerializedSelectFieldData(DynamicFormField $selectField, int $selectedValue): array
    {
        if ($selectField->getRelatedTable() === 'enum') {
            return match ($selectField->getRelatedTableCol()) {
                'JobVatMode' => [
                    'id' => JobVatMode::from($selectedValue)->value,
                    'name' => $this->translator->trans('job.vatMode.' . JobVatMode::from($selectedValue)->name)
                ],
                default => ['id' => 0, 'name' => '-']
            };
        }

        return parent::getSerializedSelectFieldData($selectField, $selectedValue);
    }

    public function getJobPositions($hideVoucherCodes = true): array
    {
        $positionsSerialized = [];
        $positionEntities = $this->jobPositionRepository->findBy(['jobId' => $this->getId()]);

        foreach ($positionEntities as $position) {
            $posItem = $position->getItemId() ? $this->itemRepository->findById($position->getItemId()) : false;
            if ($posItem) {
                $posItemTypeId = $posItem->getIntField('type_id');
                if ($posItemTypeId !== 1) {
                    $posItemType = $this->itemTypeRepository->find($posItemTypeId);

                    switch ($posItemType->getType()) {
                        case 'giftcard':
                            $voucherCodes = $this->voucherCodeRepository->findBy(['position' => $position]);
                            if ($voucherCodes) {
                                foreach ($voucherCodes as $voucherCode) {
                                    // only show partial code
                                    if ($hideVoucherCodes) {
                                        $position->addVoucherCode(explode('-', $voucherCode->getCode())[0] . '-*******');
                                    } else {
                                        $position->addVoucherCode($voucherCode->getCode());
                                    }
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
                $position->setItem($posItem);
                $position->setPrice($posItem->getPriceField('price'));
            }

            $positionsSerialized[] = $position;
        }
        return $positionsSerialized;
    }

    public function getVouchersUsed(): array
    {
        $vouchersDataSerialized = [];

        $vouchersUsed = $this->voucherCodeRepository->findAllUsagesByJobId($this->getId());
        /** @var ItemVoucherCode $voucherCode */
        foreach ($vouchersUsed as $voucherCode) {
            $voucherName = '-';
            $voucherDiscount = 0;
            $voucherDiscountText = '0.-';

            // check if voucher is a gift card
            if ($voucherCode->getItemId()) {
                $voucherItem = $this->itemRepository->findById($voucherCode->getItemId());
                $voucherName = $voucherItem->getTextField('name');
                $voucherDiscount = $voucherItem->getPriceField('price');
                $voucherDiscountText = $voucherItem->getPriceFieldText('price');
            }

            if ($voucherCode->getVoucherId()) {
                $voucher = $this->voucherRepository->findById($voucherCode->getVoucherId());
                $isAbsoluteVoucher = ItemVoucherType::from($voucher->getTextField('voucher_type')) === ItemVoucherType::ABSOLUTE;
                $voucherName = $voucher->getTextField('name');
                $voucherDiscount = $voucher->getPriceField('amount');
                $voucherDiscountText = $isAbsoluteVoucher
                    ? $voucher->getPriceFieldText('amount')
                    : $voucher->getPriceField('amount'). '%'
                ;
            }

            $vouchersDataSerialized[] = [
                'name' => $voucherName,
                'code' => $voucherCode->getCode(),
                'amount' => $voucherDiscount,
                'amount_text' => $voucherDiscountText,
            ];
        }

        return $vouchersDataSerialized;
    }
}
