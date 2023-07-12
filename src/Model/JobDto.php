<?php

namespace App\Model;

use App\Entity\DynamicFormField;
use App\Enum\JobVatMode;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\ItemRepository;
use App\Repository\JobPositionRepository;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

class JobDto extends DynamicDto
{
    public function __construct(
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
        private readonly JobPositionRepository $jobPositionRepository,
        private readonly ItemRepository $itemRepository,
    ) {
        parent::__construct($this->dynamicFormFieldRepository, $this->connection);
    }

    protected function getSerializedSelectFieldData(DynamicFormField $selectField, int $selectedValue): array
    {
        if ($selectField->getRelatedTable() === 'enum') {
            return match ($selectField->getRelatedTableCol()) {
                'JobVatMode' => [
                    'id' => JobVatMode::from($selectedValue)->value,
                    'name' => $this->translator->trans('job.vatMode.' . JobVatMode::VAT_EXCLUDED->name)
                ],
                default => ['id' => 0, 'name' => '-']
            };
        }

        return parent::getSerializedSelectFieldData($selectField, $selectedValue);
    }

    public function getJobPositions(): array
    {
        $positionsSerialized = [];
        $positionEntities = $this->jobPositionRepository->findBy(['jobId' => $this->getId()]);

        foreach ($positionEntities as $position) {
            $posItem = $position->getItemId() ? $this->itemRepository->findById($position->getItemId()) : false;
            if ($posItem) {
                $position->setItem($posItem);
                $position->setPrice($posItem->getPriceField('price'));
            }

            $positionsSerialized[] = $position;
        }
        return $positionsSerialized;
    }
}
