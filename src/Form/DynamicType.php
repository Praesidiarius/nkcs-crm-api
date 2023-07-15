<?php

namespace App\Form;

use App\Entity\DynamicFormField;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\DynamicFormRepository;
use App\Repository\SystemSettingRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class DynamicType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly DynamicFormRepository $dynamicFormRepository,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly SystemSettingRepository $systemSettings,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $formFields = $this->getFormFields( false);
        foreach ($formFields as $field) {
            switch ($field['type']) {
                case 'select':
                case 'autocomplete':
                    $builder->add($field['key'], NumberType::class);
                    break;
                case 'hidden':
                    $builder->add($field['key'], HiddenType::class);
                    break;
                case 'text':
                case 'phone':
                    $builder->add($field['key'], TextType::class);
                    break;
                case 'email':
                    $builder->add($field['key'], EmailType::class);
                    break;
                case 'currency':
                    $builder->add($field['key'], CurrencyType::class);
                    break;
                case 'date':
                    $builder->add($field['key'], DateType::class);
                    break;
                case 'textarea':
                    $builder->add($field['key'], TextareaType::class);
                    break;
                case 'checkbox':
                    $builder->add($field['key'], CheckboxType::class);
                    break;
                default:
                    break;
            };
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function getFormSections(string $formKey, $withTabs = false): array
    {
        $dynamicSections = $this->dynamicFormRepository->findOneBy(['formKey' => $formKey])->getDynamicFormSections();
        $formSections = [];
        foreach ($dynamicSections as $dynamicSection) {
            if (!$withTabs) {
                if ($dynamicSection->getParentSection() === null) {
                    continue;
                }
            }
            $formSections[] = [
                'text' => $this->translator->trans($dynamicSection->getSectionLabel()),
                'key' => $dynamicSection->getSectionKey(),
                'isTab' => $dynamicSection->getParentSection() === null,
            ];
        }

        return $formSections;
    }

    protected function getFormFieldData(DynamicFormField $formField): array|string
    {
        if ($formField->getFieldType() === 'select' || $formField->getFieldType() === 'autocomplete') {
            return $this->getDynamicListData($formField);
        }
        return $formField->getDefaultData() ?? '';
    }

    public function getFormDefaultValues(string $formKey): array
    {
        $defaultValues = [];

        $formFields = $this->getFormFields($formKey);

        /** @var DynamicFormField $field */
        foreach ($formFields as $field) {
            if (array_key_exists('default', $field)) {
                $defaultValues[$field['key']] = $field['default'];
            }
        }

        return $defaultValues;
    }


    protected function getDynamicListData(DynamicFormField $formField): array
    {
        return [];
    }

    protected function getFormFieldDefaultData(DynamicFormField $formField): string|int|float|null
    {
        return match ($formField->getFieldType()) {
            'select' => (int) $formField->getDefaultData(),
            default => $formField->getDefaultData()
        };
    }

    public function getFormFields(string $formKey, bool $withTabs = true): array
    {

        $dynamicFormFields = $this->dynamicFormFieldRepository->getUserFieldsByFormKey($formKey);

        $formFields = [];

        foreach ($dynamicFormFields as $dynamicFormField) {
            if (!$withTabs) {
                if (!$dynamicFormField->getSection()->getParentSection()) {
                    continue;
                }
            }
            $fieldApiData = [
                'text' => $this->translator->trans($dynamicFormField->getLabel()),
                'key' => $dynamicFormField->getFieldKey(),
                'type' => $dynamicFormField->getFieldType(),
                'required' => $dynamicFormField->isFieldRequired(),
                'section' => $dynamicFormField->getSection()?->getSectionKey() ?? 'none',
                'cols' => $dynamicFormField->getColumns(),
                'data' => is_array($this->getFormFieldData($dynamicFormField))
                    ? $this->getFormFieldData($dynamicFormField) : json_decode($this->getFormFieldData($dynamicFormField)),
            ];

            if ($dynamicFormField->getDefaultData()) {
                $fieldApiData['default'] = $this->getFormFieldDefaultData($dynamicFormField);
            }

            if ($dynamicFormField->getFieldType() === 'table') {
                $rowFields = $this->dynamicFormFieldRepository->findBy(['parentField' => $dynamicFormField]);
                $fieldApiData['fields'] = [];

                foreach ($rowFields as $rowField) {
                    $fieldApiData['fields'][] = [
                        // title is for vuetify datatable headers
                        'title' => $this->translator->trans($rowField->getLabel()),
                        // text is for form field labels
                        'text' => $this->translator->trans($rowField->getLabel()),
                        'key' => $rowField->getFieldKey(),
                        'value' => $rowField->getFieldKey(),
                        'type' => $rowField->getFieldType(),
                        'section' => $rowField->getSection()?->getSectionKey() ?? 'none',
                        'cols' => $rowField->getColumns(),
                    ];
                }
            }

            $formFields[] = $fieldApiData;
        }

        return $formFields;
    }

    public function getIndexHeaders(string $formKey): array
    {
        $dynamicFormFields = $this->dynamicFormFieldRepository->getUserIndexColumnsByFormKey($formKey);

        $indexHeaders = [];
        foreach ($dynamicFormFields as $indexCol) {
            $indexHeaders[] =   [
                'title' => $this->translator->trans($indexCol->getLabel()),
                'key' => $indexCol->getFieldKey(),
                'sortable' => false,
                'type' => $indexCol->getFieldType()
            ];
        }

        return $indexHeaders;
    }
}
