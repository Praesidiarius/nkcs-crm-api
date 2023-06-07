<?php

namespace App\Form\Item;

use App\Entity\Item;
use App\Entity\ItemUnit;
use App\Repository\ItemUnitRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
        private readonly ItemUnitRepository $itemUnitRepository,
        private readonly string $paymentStripeKey,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('price', NumberType::class)
            ->add('unit', EntityType::class, [
                'class' => ItemUnit::class
            ])
        ;

        if ($this->paymentStripeKey) {
            $builder->add('stripeEnabled', CheckboxType::class);
        }
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
        $itemUnits = $this->itemUnitRepository->findAll();
        $unitField = [];
        foreach ($itemUnits as $itemUnit) {
            $unitField[] = [
                'id' => $itemUnit->getId(),
                'text' => $this->translator->trans($itemUnit->getName())
            ];
        }

        $formFields = [
            [
                'text' => $this->translator->trans('item.name'),
                'key' => 'name',
                'type' => 'text',
                'section' => 'basic',
                'cols' => 6,
            ],
            [
                'text' => $this->translator->trans('item.price'),
                'key' => 'price',
                'type' => 'number',
                'section' => 'basic',
                'cols' => 4
            ],
            [
                'text' => $this->translator->trans('item.unit.unit'),
                'key' => 'unit',
                'type' => 'select',
                'section' => 'basic',
                'data' => $unitField,
                'cols' => 2,
            ],
        ];

        if ($this->paymentStripeKey) {
            $formFields[] = [
                'text' => $this->translator->trans('item.addToStripe'),
                'key' => 'stripeEnabled',
                'type' => 'checkbox',
                'section' => 'basic',
                'cols' => 12
            ];
        }

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
            [
                'title' => $this->translator->trans('item.unit.unit'),
                'key' => 'unit',
                'sortable' => false,
                'type' => 'object',
                'label_key' => 'name',
            ],
        ];

        return $indexHeaders;
    }
}
