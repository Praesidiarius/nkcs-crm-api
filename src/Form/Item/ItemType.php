<?php

namespace App\Form\Item;

use App\Entity\DynamicFormField;
use App\Form\DynamicType;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\DynamicFormRepository;
use App\Repository\ItemUnitRepository;
use App\Repository\SystemSettingRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class ItemType extends DynamicType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ItemUnitRepository $itemUnitRepository,
        private readonly DynamicFormRepository $dynamicFormRepository,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly SystemSettingRepository $systemSettings,
    )
    {
        parent::__construct(
            $this->translator,
            $this->dynamicFormRepository,
            $this->dynamicFormFieldRepository,
            $this->systemSettings,
        );
    }

    public function getFormSections(string $formKey = 'item', $withTabs = false): array
    {
        return parent::getFormSections('item', $withTabs);
    }

    protected function getDynamicListData(DynamicFormField $formField): array
    {
        return match ($formField->getRelatedTable()) {
            'item_unit' => $this->getUnits(),
            default => parent::getDynamicListData($formField)
        };
    }

    private function getUnits() : array
    {
        $itemUnits = $this->itemUnitRepository->findAll();
        $unitField = [];
        foreach ($itemUnits as $unit) {
            $unitField[] = [
                'id' => $unit->getId(),
                'text' => $this->translator->trans($unit->getName()),
            ];
        }

        return $unitField;
    }

    public function getFormFields(string $formKey = 'item', bool $withTabs = true): array
    {
        return parent::getFormFields('item', $withTabs);
    }

    public function getIndexHeaders(string $formKey = 'item'): array
    {
        return parent::getIndexHeaders('item');
    }
}
