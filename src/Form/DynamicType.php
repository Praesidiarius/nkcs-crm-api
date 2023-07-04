<?php

namespace App\Form;

use App\Entity\DynamicFormField;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\DynamicFormRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class DynamicType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        //private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly DynamicFormRepository $dynamicFormRepository,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $formFields = $this->getFormFields( false);
        foreach ($formFields as $field) {
            switch ($field['type']) {
                case 'select':
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
                'test' => $withTabs
            ];
        }

        return $formSections;
    }

    private function getFormFieldData(DynamicFormField $formField): mixed
    {
        return [];
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
                'section' => $dynamicFormField->getSection()->getSectionKey(),
                'cols' => $dynamicFormField->getColumns(),
                'data' => is_array($this->getFormFieldData($dynamicFormField))
                    ? '----' : json_decode($this->getFormFieldData($dynamicFormField)),
            ];

            if ($dynamicFormField->getFieldType() === 'table') {
                $rowFields = $this->dynamicFormFieldRepository->findBy(['parentField' => $dynamicFormField]);
                $fieldApiData['fields'] = [];

                foreach ($rowFields as $rowField) {
                    $fieldApiData['fields'][] = [
                        'text' => $this->translator->trans($rowField->getLabel()),
                        'key' => $rowField->getFieldKey(),
                        'value' => $rowField->getFieldKey(),
                        'type' => $rowField->getFieldType(),
                        'section' => $rowField->getSection()->getSectionKey(),
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
