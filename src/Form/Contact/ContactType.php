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

class ContactType extends AbstractType
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
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

    public function getFormFields(): array
    {
        $formFields = [
            [
                'text' => 'Vorname',
                'key' => 'firstName',
                'type' => 'text'
            ],
            [
                'text' => 'Nachname',
                'key' => 'lastName',
                'type' => 'text'
            ],
            [
                'text' => 'E-Mail Privat',
                'key' => 'emailPrivate',
                'type' => 'email'
            ],
        ];

        return $formFields;
    }

    public function getIndexHeaders(): array
    {
        $indexHeaders = [
            [
                'text' => 'Vorname',
                'value' => 'firstName',
                'sortable' => true,
                'type' => 'text'
            ],
            [
                'text' => 'Nachname',
                'value' => 'lastName',
                'sortable' => true,
                'type' => 'text'
            ],
        ];

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $indexHeaders[] =  [
                'text' => 'E-Mail (Privat)',
                'value' => 'emailPrivate',
                'sortable' => true,
                'type' => 'email'
            ];
        }

        return $indexHeaders;
    }
}
