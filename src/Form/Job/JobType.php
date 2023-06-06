<?php

namespace App\Form\Job;

use App\Entity\Job;
use App\Repository\ContactRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class JobType extends AbstractType
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TranslatorInterface $translator,
        private readonly ContactRepository $contactRepository,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'invalid_message' => 'You entered an invalid value, it should include %num% letters',
                'invalid_message_parameters' => ['%num%' => 6],
            ])
            ->add('contact', NumberType::class, [
                'required' => true,
            ])
        ;
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
            ]
        ];

        return $formSections;
    }

    public function getFormFields(): array
    {
        $contacts = $this->contactRepository->findAll();
        $contactField = [];
        foreach ($contacts as $contact) {
            $contactField[] = [
                'id' => $contact->getId(),
                'text' => $contact->getName()
            ];
        }

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
                'type' => 'text'
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
