<?php

namespace App\Form\Item;

use App\Entity\DynamicFormField;
use App\Enum\ItemVoucherType;
use App\Form\DynamicType;
use App\Repository\ContactRepository;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\DynamicFormRepository;
use App\Repository\SystemSettingRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class VoucherType extends DynamicType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly DynamicFormRepository $dynamicFormRepository,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly SystemSettingRepository $systemSettings,
        private readonly ContactRepository $contactRepository,
    )
    {
        parent::__construct(
            $this->translator,
            $this->dynamicFormRepository,
            $this->dynamicFormFieldRepository,
            $this->systemSettings,
        );
    }

    protected function getDynamicListData(DynamicFormField $formField): array
    {
        return match ($formField->getRelatedTable()) {
            'contact' => $this->getContacts(),
            'enum' => $this->getEnumListData($formField),
            default => parent::getDynamicListData($formField)
        };
    }

    private function getContacts() : array
    {
        $contacts = $this->contactRepository->findAll();
        $contactField = [];
        foreach ($contacts as $contact) {
            $contactField[] = [
                'id' => $contact->getId(),
                'text' => $contact->getBoolField('is_company')
                    ? $contact->getTextField('company_name')
                    : $contact->getTextField('first_name')
                    . ($contact->getTextField('last_name') ? ' ' . $contact->getTextField('last_name') : '')
            ];
        }

        return $contactField;
    }

    private function getEnumListData(DynamicFormField $formField): array
    {
        $data = [];
        switch ($formField->getRelatedTableCol()) {
            case 'ItemVoucherType':
                $data[] = [
                    'id' => ItemVoucherType::ABSOLUTE->value,
                    'text' => $this->translator->trans('item.voucher.type.' . ItemVoucherType::ABSOLUTE->name),
                ];
                $data[] = [
                    'id' => ItemVoucherType::PERCENT->value,
                    'text' => $this->translator->trans('item.voucher.type.' . ItemVoucherType::PERCENT->name),
                ];
                break;
            default:
                break;
        }
        return $data;
    }

    public function getFormSections(string $formKey = 'voucher', $withTabs = false): array
    {
        return parent::getFormSections('voucher', $withTabs);
    }

    public function getFormFields(string $formKey = 'voucher', bool $withTabs = true): array
    {
        return parent::getFormFields('voucher', $withTabs);
    }

    public function getIndexHeaders(string $formKey = 'voucher'): array
    {
        return parent::getIndexHeaders('voucher');
    }
}
