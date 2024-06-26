<?php

namespace App\Model;

use App\Entity\DynamicFormField;
use App\Repository\DynamicFormFieldRepository;
use App\Service\ChartDataGenerator;
use DateTimeInterface;
use Doctrine\DBAL\Connection;

class DynamicDto
{
    private array $serializedData = [];
    private array $data = [];
    public function __construct(
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly Connection $connection,
        private readonly ?ChartDataGenerator $chartDataGenerator = null,
    ) {
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function addCustomData(string $key, array|int|string $data): void
    {
        $this->data[$key] = $data;
    }

    public function setId(int $id): void
    {
        $this->data['id'] = $id;
    }

    public function getId(): ?int
    {
        return $this->data['id'] ?? null;
    }

    public function getTextField(string $fieldKey): ?string
    {
        if (array_key_exists($fieldKey, $this->data)) {
            return $this->data[$fieldKey];
        }

        return null;
    }

    public function getDateFormattedField(string $fieldKey): ?string
    {
        if (array_key_exists($fieldKey, $this->data)) {
            if ($this->data[$fieldKey]) {
                return date('d.m.Y', strtotime($this->data[$fieldKey]));
            }
        }

        return '-';
    }

    public function setTextField(string $fieldKey, string $text): self
    {
        $this->data[$fieldKey] = $text;

        return $this;
    }

    public function setIntField(string $fieldKey, int $value): self
    {
        $this->data[$fieldKey] = $value;

        return $this;
    }

    public function getIntField(string $fieldKey): int
    {
        return (int) $this->data[$fieldKey] ?? 0;
    }

    public function setDateField(string $fieldKey, DateTimeInterface $dateTime): self
    {
        $this->data[$fieldKey] = $dateTime->format('Y-m-d');

        return $this;
    }

    public function getPriceField(string $fieldKey): ?float
    {
        if (array_key_exists($fieldKey, $this->data)) {
            return (float) $this->data[$fieldKey];
        }

        return null;
    }

    public function getPriceFieldText(string $fieldKey): string
    {
        $priceText = number_format($this->getPriceField($fieldKey), 2, '.', '\'');
        if (fmod($this->getPriceField($fieldKey), 1) === 0.0) {
            $priceText = number_format($this->getPriceField($fieldKey), 0, '.', '\'') . '.-';
        }
        return $priceText;
    }

    public function setPriceField(string $fieldKey, float $price): self
    {
        $this->data[$fieldKey] = $price;

        return $this;
    }

    public function getBoolField(string $fieldKey): bool
    {
        if (array_key_exists($fieldKey, $this->data)) {
            return (bool) $this->data[$fieldKey];
        }

        return false;
    }

    public function setBoolField(string $fieldKey, bool $active): self
    {
        $this->data[$fieldKey] = (int) $active;

        return $this;
    }

    public function getSelectField(string $fieldKey): array
    {
        $field = $this->dynamicFormFieldRepository->findOneBy(['fieldKey' => $fieldKey]);

        return $this->getSerializedSelectFieldData($field, $this->data[$fieldKey] ?? 0);
    }

    public function setSelectField(string $fieldKey, int $value): self
    {
        $this->data[$fieldKey] = $value;

        return $this;
    }

    public function serializeDataForApiByFormModel(string $formKey): void
    {
        $this->serializedData = [
            'id' => $this->data['id']
        ];

        $formFields = $this->dynamicFormFieldRepository->getUserFieldsByFormKey($formKey);

        foreach ($formFields as $field) {
            $this->serializedData[$field->getFieldKey()] = match ($field->getFieldType()) {
                'select', 'autocomplete' => $this->getSerializedSelectFieldData(
                    $field,
                    (
                        is_array($this->data[$field->getFieldKey()] ?? false)
                            ? $this->data[$field->getFieldKey()]['id']
                            : $this->data[$field->getFieldKey()] ?? 0
                    ) ?? 0,
                ),
                'chart' => $this->chartDataGenerator
                    ? $this->chartDataGenerator->getSerializedChartData($field->getFieldKey(), $this->getId())
                    : []
                ,
                'table' => $this->getSerializedTableFieldData($field),
                'currency' => $this->getSerializedCurrencyFieldData($field, $this->data[$field->getFieldKey()] ?? 0),
                default => array_key_exists($field->getFieldKey(), $this->data)
                    ? $this->data[$field->getFieldKey()]
                    : '-'
            };
        }
    }

    private function getSerializedTableFieldData(DynamicFormField $formField): array
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('*')
            ->from($formField->getRelatedTable())
            ->where($formField->getRelatedTableCol() . ' = :id')
            ->setParameters([
                'id' => $this->getId()
            ]);

        if ($formField->getRelatedTableOrder()) {
            $orderBy = explode(' ', $formField->getRelatedTableOrder());
            $qb->orderBy($orderBy[0], $orderBy[1]);
        }

        return $qb->fetchAllAssociative();
    }

    private function getSerializedCurrencyFieldData(DynamicFormField $currencyField, float $value): float
    {
        $this->serializedData[$currencyField->getFieldKey() . '_text'] = 'CHF ' . number_format($value, 2, '.', '\'');
        return $value;
    }

    protected function getSerializedSelectFieldData(DynamicFormField $selectField, int $selectedValue): array
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('*')
            ->from($selectField->getRelatedTable())
            ->where('id = :id')
            ->setParameters([
                'id' => $selectedValue
            ]);

        $result = $qb->fetchAssociative();

        if ($result) {
            $info = ['id' => $selectedValue, 'name' => $result[$selectField->getRelatedTableCol()]];
            if (array_key_exists('type', $result)) {
                $info['type'] = $result['type'];
            }
            return $info;
        }

        return ['id' => 0, 'name' => '-'];
    }

    public function getDataSerialized(): array
    {
        return $this->serializedData;
    }

    public function setCreatedBy(int $userId): void
    {
        $this->data['created_by'] = $userId;
    }

    public function setCreatedDate(): void
    {
        $this->data['created_date'] = date('Y-m-d H:i:s', time());
    }
}
