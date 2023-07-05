<?php

namespace App\Form\Job;

use App\Entity\Job;
use App\Enum\JobVatMode;
use App\Repository\ContactRepository;
use App\Repository\SystemSettingRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class JobType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ContactRepository $contactRepository,
        private readonly SystemSettingRepository $systemSettingRepository,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //$builder;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Job::class,
            'csrf_protection' => false,
        ]);
    }

    public function getFormSections(): array
    {
        $formSections = [
            [
                'text' => $this->translator->trans('job.form.section.basic'),
                'key' => 'basic',
            ],
            [
                'text' => $this->translator->trans('job.form.section.settings'),
                'key' => 'settings',
            ]
        ];

        return $formSections;
    }

    public function getFormDefaultValues(): array
    {
        $defaultValues = [];

        $formFields = $this->getFormFields();

        foreach ($formFields as $field) {
            if (array_key_exists('default', $field)) {
                $defaultValues[$field['key']] = $field['default'];
            }
        }

        return $defaultValues;
    }

    public function getFormFields(): array
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
                ,
            ];
        }

        $vatDefaultMode = $this->systemSettingRepository
            ->findSettingByKey('job-vat-default-mode')
            ?->getSettingValue()
            ?? 0
        ;

        $formFields = [
            [
                'text' => $this->translator->trans('job.title'),
                'key' => 'title',
                'type' => 'text',
                'section' => 'basic',
                'cols' => 6,
            ],
            [
                'text' => $this->translator->trans('job.contact'),
                'key' => 'contact',
                'type' => 'autocomplete',
                'section' => 'basic',
                'data' => $contactField,
                'cols' => 6,
            ],
            [
                'text' => $this->translator->trans('job.vat.vat'),
                'key' => 'vatMode',
                'type' => 'select',
                'section' => 'settings',
                'default' => (int) $vatDefaultMode,
                'data' => [
                    ['id' => JobVatMode::VAT_NONE, 'text' => $this->translator->trans('job.vat.none')],
                    ['id' => JobVatMode::VAT_DEFAULT, 'text' => $this->translator->trans('job.vat.default')],
                ],
                'cols' => 6,
            ],
        ];

        return $formFields;
    }

    public function getIndexHeaders(): array
    {
        $indexHeaders = [
            [
                'title' => $this->translator->trans('job.id'),
                'key' => 'id',
                'sortable' => false,
                'type' => 'text',
                'width' => '25px',
            ],
            [
                'title' => $this->translator->trans('job.title'),
                'key' => 'title',
                'sortable' => false,
                'type' => 'text'
            ],
            [
                'title' => $this->translator->trans('job.contact'),
                'key' => 'contact',
                'sortable' => false,
                'type' => 'select'
            ],
        ];

        return $indexHeaders;
    }
}
