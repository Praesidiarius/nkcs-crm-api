<?php

namespace App\Form\Contact;

use App\Entity\Contact;
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
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'required' => true,
                'invalid_message' => 'You entered an invalid value, it should include %num% letters',
                'invalid_message_parameters' => ['%num%' => 6],
            ])
            ->add('lastName', TextType::class)
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

    public function getFormFields(): array
    {
        $formFields = [
            [
                'text' => $this->translator->trans('firstname'),
                'key' => 'firstName',
                'type' => 'text',
                'section' => 'basic',
                'cols' => 4,
            ],
            [
                'text' => $this->translator->trans('lastname'),
                'key' => 'lastName',
                'type' => 'text',
                'section' => 'basic',
                'cols' => 4
            ],
            [
                'text' => $this->translator->trans('email.private'),
                'key' => 'emailPrivate',
                'type' => 'email',
                'section' => 'basic',
                'cols' => 12
            ],
            [
                'text' =>  $this->translator->trans('addresses'),
                'key' => 'address',
                'type' => 'table',
                'section' => 'address',
                'cols' => 12,
                'fields' => [
                    [
                        'title' => $this->translator->trans('address.street'),
                        'key' => 'street',
                        'value' => 'street',
                        'type' => 'text',
                        'section' => 'address',
                        'cols' => 6
                    ],
                    [
                        'title' => $this->translator->trans('address.zip'),
                        'key' => 'zip',
                        'value' => 'zip',
                        'type' => 'text',
                        'section' => 'address',
                        'cols' => 1
                    ],
                    [
                        'title' => $this->translator->trans('address.city'),
                        'key' => 'city',
                        'value' => 'city',
                        'type' => 'text',
                        'section' => 'address',
                        'cols' => 5
                    ],
                ]
            ],
        ];

        return $formFields;
    }

    public function getIndexHeaders(): array
    {
        $indexHeaders = [
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
