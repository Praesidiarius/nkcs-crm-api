<?php

namespace App\Form\Item;

use App\Form\DynamicType;
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
    )
    {
        parent::__construct(
            $this->translator,
            $this->dynamicFormRepository,
            $this->dynamicFormFieldRepository,
            $this->systemSettings,
        );
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
