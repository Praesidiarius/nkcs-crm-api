<?php

namespace App\Model;

use App\Entity\DynamicFormField;
use App\Enum\ItemVoucherType;
use App\Repository\DynamicFormFieldRepository;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

class VoucherDto extends DynamicDto
{
    public function __construct(
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($this->dynamicFormFieldRepository, $this->connection);
    }

    protected function getSerializedSelectFieldData(DynamicFormField $selectField, int|string $selectedValue): array
    {
        if ($selectField->getRelatedTable() === 'enum') {
            return match ($selectField->getRelatedTableCol()) {
                'ItemVoucherType' => [
                    'id' => ItemVoucherType::from($selectedValue)->value,
                    'name' => $this->translator->trans('item.voucher.type.' . ItemVoucherType::from($selectedValue)->name)
                ],
                default => ['id' => 0, 'name' => '-']
            };
        }

        return parent::getSerializedSelectFieldData($selectField, $selectedValue);
    }
}
