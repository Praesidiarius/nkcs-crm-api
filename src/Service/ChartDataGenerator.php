<?php

namespace App\Service;

use App\Repository\DynamicFormFieldRepository;
use App\Repository\ItemPriceHistoryRepository;
use Cake\Chronos\Chronos;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class ChartDataGenerator
{
    public function __construct(
        private ItemPriceHistoryRepository $priceHistoryRepository,
        private DynamicFormFieldRepository $dynamicFormFieldRepository,
        private TranslatorInterface $translator,
    ) {
    }

    public function getSerializedChartData(string $chartKey, int $relatedEntityId): array
    {
        return match ($chartKey) {
            'price_history' => $this->getPriceHistoryChartData($relatedEntityId),
            default => []
        };
    }

    private function getPriceHistoryChartData(int $itemId): array
    {
        $itemPrices = $this->priceHistoryRepository->getPricesForItem($itemId);

        $priceHistoryFields = $this->dynamicFormFieldRepository->getUserFieldsByFormKey('itemPrice');
        $priceFields = [];
        foreach ($priceHistoryFields as $formField) {
            if (str_starts_with($formField->getFieldKey(), 'price_')) {
                $priceFields[] = $formField;
            }
        }

        $chartData = [];
        foreach ($priceFields as $priceField) {
            $priceData = [];
            foreach ($itemPrices as $itemPrice) {
                if (array_key_exists($formField->getFieldKey(), $itemPrice)) {
                    $priceData[] = $itemPrice[$priceField->getFieldKey()];
                }
            }
            $chartData[] = [
                'name' => $this->translator->trans($priceField->getLabel()),
                'color' => '',
                'data' => $priceData,
            ];
        }

        $chartCategories = [];
        foreach ($itemPrices as $itemPrice) {
            $chartCategories[] = Chronos::parse($itemPrice['date'])->format('Y-m-d');
        }

        return [
            'data' => $chartData,
            'categories' => $chartCategories,
        ];
    }
}