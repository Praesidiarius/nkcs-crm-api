<?php

namespace App\Form\Item;

use App\Entity\Item;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ItemType extends AbstractType
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
            ->add('name', TextType::class)
            ->add('price', NumberType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Item::class,
            'csrf_protection' => false,
        ]);
    }

    public function getFormSections(): array
    {
        $formSections = [
            [
                'text' => $this->translator->trans('item.form.section.basic'),
                'key' => 'basic',
            ]
        ];

        return $formSections;
    }

    public function getFormFields(): array
    {
        $formFields = [
            [
                'text' => $this->translator->trans('item.name'),
                'key' => 'name',
                'type' => 'text',
                'section' => 'basic',
                'cols' => 8,
            ],
            [
                'text' => $this->translator->trans('item.price'),
                'key' => 'price',
                'type' => 'number',
                'section' => 'basic',
                'cols' => 4
            ]
        ];

        return $formFields;
    }

    public function getIndexHeaders(): array
    {
        $indexHeaders = [
            [
                'title' => $this->translator->trans('item.name'),
                'key' => 'name',
                'sortable' => false,
                'type' => 'text'
            ],
            [
                'title' => $this->translator->trans('item.price'),
                'key' => 'price',
                'sortable' => false,
                'type' => 'numberFloat-2'
            ],
        ];

        return $indexHeaders;
    }
}