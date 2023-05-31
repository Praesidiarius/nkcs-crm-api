<?php

namespace App\Form\Contact;

use App\Entity\Job;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
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
                'text' => $this->translator->trans('job.title'),
                'key' => 'title',
                'type' => 'text',
                'section' => 'basic',
                'cols' => 12,
            ],
        ];

        return $formFields;
    }

    public function getIndexHeaders(): array
    {
        $indexHeaders = [
            [
                'title' => $this->translator->trans('job.title'),
                'key' => 'title',
                'sortable' => false,
                'type' => 'text'
            ]
        ];

        return $indexHeaders;
    }
}
