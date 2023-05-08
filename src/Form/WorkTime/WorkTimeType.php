<?php

namespace App\Form\WorkTime;

use App\Entity\Worktime;
use DateTime;
use DateTimeInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class WorkTimeType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class)
            ->add('start', TimeType::class)
            ->add('end', TimeType::class)
            ->add('comment', TextareaType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Worktime::class,
            'csrf_protection' => false,
        ]);
    }

    public function getFormSections(): array
    {
        $formSections = [
            [
                'text' => $this->translator->trans('work-time.form.section.basic'),
                'key' => 'basic',
            ],
        ];

        return $formSections;
    }

    public function getFormFields(): array
    {
        $formFields = [
            [
                'text' => $this->translator->trans('time.date'),
                'key' => 'date',
                'type' => 'date',
                'section' => 'basic',
                'cols' => 4,
            ],
            [
                'text' => $this->translator->trans('work-time.start'),
                'key' => 'start',
                'type' => 'time',
                'section' => 'basic',
                'cols' => 4
            ],
            [
                'text' => $this->translator->trans('work-time.end'),
                'key' => 'end',
                'type' => 'time',
                'section' => 'basic',
                'cols' => 4
            ],
            [
                'text' => $this->translator->trans('comment'),
                'key' => 'comment',
                'type' => 'textarea',
                'section' => 'basic',
                'cols' => 12
            ],
        ];

        return $formFields;
    }

    public function getIndexHeaders(): array
    {
        $indexHeaders = [
            [
                'text' => $this->translator->trans('time.date'),
                'value' => 'date',
                'sortable' => true,
                'type' => 'date'
            ],
            [
                'text' => $this->translator->trans('work-time.start'),
                'value' => 'start',
                'sortable' => true,
                'type' => 'time'
            ],
            [
                'text' => $this->translator->trans('work-time.end'),
                'value' => 'end',
                'sortable' => true,
                'type' => 'time'
            ],
        ];

        return $indexHeaders;
    }
}
