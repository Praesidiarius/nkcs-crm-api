<?php

namespace App\Form\Contact;

use App\Entity\DynamicFormField;
use App\Form\DynamicType;
use App\Repository\ContactSalutionRepository;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\DynamicFormRepository;
use App\Repository\SystemSettingRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactType extends DynamicType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ContactSalutionRepository $contactSalutionRepository,
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

    public function getFormSections(string $formKey = 'contact', $withTabs = false): array
    {
        return parent::getFormSections('contact', $withTabs);
    }

    protected function getDynamicListData(DynamicFormField $formField): array
    {
        return match ($formField->getRelatedTable()) {
            'contact_salution' => $this->getSalutions(),
            default => parent::getDynamicListData($formField)
        };
    }

    private function getSalutions() : array
    {
        $contactSalutions = $this->contactSalutionRepository->findAll();
        $salutionField = [];
        foreach ($contactSalutions as $salution) {
            $salutionField[] = [
                'id' => $salution->getId(),
                'text' => $this->translator->trans($salution->getName()),
            ];
        }

        return $salutionField;
    }

    public function getFormFields(string $formKey = 'contact', bool $withTabs = true): array
    {
        return parent::getFormFields('contact', $withTabs);
    }

    public function getIndexHeaders(string $formKey = 'contact'): array
    {
        return parent::getIndexHeaders('contact');
    }
}
