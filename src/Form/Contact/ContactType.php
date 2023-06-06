<?php

namespace App\Form\Contact;

use App\Entity\Contact;
use App\Entity\ContactSalution;
use App\Repository\ContactSalutionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly ContactSalutionRepository $contactSalutionRepository,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('salution', EntityType::class, [
                'class' => ContactSalution::class,
            ])
            ->add('firstName', TextType::class, [
                'required' => true,
                'invalid_message' => 'You entered an invalid value, it should include %num% letters',
                'invalid_message_parameters' => ['%num%' => 6],
            ])
            ->add('companyUid', TextType::class)
            ->add('companyName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('phone', TextType::class)
            ->add('isCompany', CheckboxType::class)
            ->add('emailPrivate', EmailType::class)
            ->add('emailBusiness', EmailType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
            'csrf_protection' => false,
        ]);
    }

    public function getFormSections(): array
    {
        $formSections = [
            [
                'text' => $this->translator->trans('contact.form.section.basic'),
                'key' => 'basic',
            ],
            [
                'text' => $this->translator->trans('contact.form.section.addresses'),
                'key' => 'address',
            ]
        ];

        return $formSections;
    }

    public function getFormFields($isCompany = false): array
    {
        $contactSalutions = $this->contactSalutionRepository->findAll();
        $salutionField = [];
        foreach ($contactSalutions as $salution) {
            $salutionField[] = [
                'id' => $salution->getId(),
                'text' => $this->translator->trans($salution->getName()),
            ];
        }
        $formFields = [];

        if (!$isCompany) {
            $formFields[] = [
                'text' => $this->translator->trans('salution'),
                'key' => 'salution',
                'type' => 'select',
                'section' => 'basic',
                'cols' => 2,
                'data' => $salutionField,
            ];
            $formFields[] = [
                'text' => $this->translator->trans('firstname'),
                'key' => 'firstName',
                'type' => 'text',
                'section' => 'basic',
                'cols' => 5,
            ];
            $formFields[] = [
                'text' => $this->translator->trans('lastname'),
                'key' => 'lastName',
                'type' => 'text',
                'section' => 'basic',
                'cols' => 5
            ];
        } else {
            $formFields[] = [
                'text' => $this->translator->trans('company'),
                'key' => 'companyName',
                'type' => 'text',
                'section' => 'basic',
                'cols' => 8
            ];
            $formFields[] = [
                'text' => $this->translator->trans('companyUid'),
                'key' => 'companyUid',
                'type' => 'text',
                'section' => 'basic',
                'cols' => 4
            ];
        }

        $baseFields = [
            [
                'text' => $this->translator->trans('email.address'),
                'key' => 'emailPrivate',
                'type' => 'email',
                'section' => 'basic',
                'cols' => 8
            ],
            [
                'text' => $this->translator->trans('phone'),
                'key' => 'phone',
                'type' => 'phone',
                'section' => 'basic',
                'cols' => 4
            ],
            [
                'text' =>  $this->translator->trans('addresses'),
                'key' => 'address',
                'type' => 'table',
                'section' => 'address',
                'cols' => 12,
                'fields' => [
                    [
                        'text' => $this->translator->trans('address.street'),
                        'key' => 'street',
                        'value' => 'street',
                        'type' => 'text',
                        'section' => 'address',
                        'cols' => 6
                    ],
                    [
                        'text' => $this->translator->trans('address.zip'),
                        'key' => 'zip',
                        'value' => 'zip',
                        'type' => 'text',
                        'section' => 'address',
                        'cols' => 1
                    ],
                    [
                        'text' => $this->translator->trans('address.city'),
                        'key' => 'city',
                        'value' => 'city',
                        'type' => 'text',
                        'section' => 'address',
                        'cols' => 5
                    ],
                ]
            ],
        ];

        return array_merge($formFields, $baseFields);
    }

    public function getIndexHeaders(): array
    {
        $indexHeaders = [
            [
                'title' => $this->translator->trans('company'),
                'key' => 'companyName',
                'sortable' => false,
                'type' => 'text'
            ],
            [
                'title' => $this->translator->trans('salution'),
                'key' => 'salution',
                'sortable' => false,
                'type' => 'text'
            ],
            [
                'title' => $this->translator->trans('firstname'),
                'key' => 'firstName',
                'sortable' => false,
                'type' => 'text'
            ],
            [
                'title' => $this->translator->trans('lastname'),
                'key' => 'lastName',
                'sortable' => false,
                'type' => 'text'
            ],
        ];

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $indexHeaders[] =  [
                'title' => $this->translator->trans('email.private'),
                'key' => 'emailPrivate',
                'sortable' => false,
                'type' => 'email'
            ];
        }

        return $indexHeaders;
    }
}
