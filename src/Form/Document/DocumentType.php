<?php

namespace App\Form\Document;

use App\Entity\Document;
use App\Repository\DocumentTypeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class DocumentType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly DocumentTypeRepository $documentTypeRepository,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'invalid_message' => 'You entered an invalid value, it should include %num% letters',
                'invalid_message_parameters' => ['%num%' => 6],
            ])
            ->add('template', FileType::class, [
                'label' => 'Vorlage (Word Datei)',

                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,

                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid Word document',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
            'csrf_protection' => false,
        ]);
    }

    public function getFormSections(): array
    {
        return [
            [
                'text' => $this->translator->trans('document.form.section.basic'),
                'key' => 'basic',
            ]
        ];
    }

    public function getFormFields(): array
    {
        $docTypes = $this->documentTypeRepository->findAll();
        $docTypeField = [];
        foreach ($docTypes as $docType) {
            $docTypeField[] = [
                'id' => $docType->getId(),
                'text' => $this->translator->trans($docType->getName())
            ];
        }

        return [
            [
                'text' => $this->translator->trans('name'),
                'key' => 'name',
                'type' => 'text',
                'section' => 'basic',
                'cols' => 4,
            ],
            [
                'text' => $this->translator->trans('document.type'),
                'key' => 'type',
                'type' => 'select',
                'data' => $docTypeField,
                'section' => 'basic',
                'cols' => 4,
            ],
            [
                'text' => $this->translator->trans('document.template'),
                'key' => 'template',
                'type' => 'file',
                'section' => 'basic',
                'cols' => 4,
            ],
        ];
    }

    public function getIndexHeaders(): array
    {
        return [
            [
                'title' => $this->translator->trans('name'),
                'key' => 'name',
                'sortable' => false,
                'type' => 'text'
            ]
        ];
    }
}
