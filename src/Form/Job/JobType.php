<?php

namespace App\Form\Job;

use App\Entity\DynamicFormField;
use App\Enum\JobVatMode;
use App\Form\DynamicType;
use App\Repository\ContactRepository;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\DynamicFormRepository;
use App\Repository\SystemSettingRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class JobType extends DynamicType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly DynamicFormRepository $dynamicFormRepository,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly ContactRepository $contactRepository,
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

    public function getFormSections(string $formKey = 'job', $withTabs = false): array
    {
        return parent::getFormSections('jobType1', $withTabs);
    }

    protected function getDynamicListData(DynamicFormField $formField): array
    {
        return match ($formField->getRelatedTable()) {
            'contact' => $this->getContacts(),
            'enum' => $this->getEnumListData($formField),
            default => parent::getDynamicListData($formField)
        };
    }

    private function getEnumListData(DynamicFormField $formField): array
    {
        $data = [];
        switch ($formField->getRelatedTableCol()) {
            case 'JobVatMode':
                $isVatEnabled = (bool) $this->systemSettings
                    ->findSettingByKey('job-vat-enabled'
                    )?->getSettingValue() ?? false
                ;

                if ($isVatEnabled) {
                    $data[] = [
                        'id' => JobVatMode::VAT_EXCLUDED->value,
                        'text' => $this->translator->trans('job.vatMode.' . JobVatMode::VAT_EXCLUDED->name),
                    ];
                    $data[] = [
                        'id' => JobVatMode::VAT_INCLUDED->value,
                        'text' => $this->translator->trans('job.vatMode.' . JobVatMode::VAT_INCLUDED->name),
                    ];
                } else {
                    $data[] = [
                        'id' => JobVatMode::VAT_NONE->value,
                        'text' => $this->translator->trans('job.vatMode.' . JobVatMode::VAT_NONE->name),
                    ];
                }
                break;
            default:
                break;
        }
        return $data;
    }

    protected function getFormFieldDefaultData(DynamicFormField $formField): string|int|float|null
    {
        return match($formField->getFieldKey()) {
            'vat_mode' => $this->getDefaultVatMode(),
            default => parent::getFormFieldDefaultData($formField)
        };
    }

    private function getDefaultVatMode(): int
    {
        $isVatEnabled = (bool) $this->systemSettings
            ->findSettingByKey('job-vat-enabled'
            )?->getSettingValue() ?? false
        ;

        if (!$isVatEnabled) {
            return JobVatMode::VAT_NONE->value;
        }

        // cast system setting to enum to be sure its valid
        $vatDefaultMode = JobVatMode::from(
            (int) $this->systemSettings
                ->findSettingByKey('job-vat-default'
                )?->getSettingValue() ?? JobVatMode::VAT_EXCLUDED->value
        );

        return $vatDefaultMode->value;
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

    public function getFormFields(string $formKey = 'job', bool $withTabs = true): array
    {
        return parent::getFormFields('jobType1', $withTabs);
    }

    public function getIndexHeaders(string $formKey = 'job'): array
    {
        return parent::getIndexHeaders('jobType1');
    }
}
