<?php

namespace App\Form\Item;

use App\Entity\DynamicFormField;
use App\Form\DynamicType;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\DynamicFormRepository;
use App\Repository\ItemTypeRepository;
use App\Repository\ItemUnitRepository;
use App\Repository\SystemSettingRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ItemType extends DynamicType
{
    private string $formKey = 'item';

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ItemUnitRepository $itemUnitRepository,
        private readonly ItemTypeRepository $itemTypeRepository,
        private readonly DynamicFormRepository $dynamicFormRepository,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly SystemSettingRepository $systemSettings,
        private readonly SerializerInterface $serializer,
    )
    {
        parent::__construct(
            $this->translator,
            $this->dynamicFormRepository,
            $this->dynamicFormFieldRepository,
            $this->systemSettings,
            $this->serializer,
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
            'item_type' => $this->getTypes(),
            default => parent::getDynamicListData($formField)
        };
    }

    private function getTypes() : array
    {
        $itemUnits = $this->itemTypeRepository->findAll();
        $unitField = [];
        foreach ($itemUnits as $unit) {
            $unitField[] = [
                'id' => $unit->getId(),
                'text' => $this->translator->trans($unit->getName()),
            ];
        }

        return $unitField;
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

    /**
     * @return DynamicFormField[]
     */
    public function getFormFields(string $formKey = 'item', bool $withTabs = true): array
    {
        return parent::getFormFields('item', $withTabs);
    }

    public function hasTableFieldForUser(string $fieldKey): bool
    {
        foreach ($this->dynamicFormFieldRepository->getUserFieldsByFormKey($this->formKey) as $formField) {
            if ($formField->getFieldKey() === $fieldKey && $formField->getFieldType() === 'table') {
                return true;
            }
        }

        return false;
    }

    public function getIndexHeaders(string $formKey = 'item'): array
    {
        return parent::getIndexHeaders('item');
    }
}
