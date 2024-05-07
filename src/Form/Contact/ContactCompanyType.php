<?php

namespace App\Form\Contact;

use App\Form\DynamicType;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\DynamicFormRepository;
use App\Repository\SystemSettingRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactCompanyType extends DynamicType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
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

    public function getFormSections(string $formKey = 'company', $withTabs = false): array
    {
        return parent::getFormSections('company', $withTabs);
    }

    public function getFormFields(string $formKey = 'company', bool $withTabs = true): array
    {
        return parent::getFormFields('company', $withTabs);
    }

    public function getIndexHeaders(string $formKey = 'company'): array
    {
        return parent::getIndexHeaders('company');
    }
}
