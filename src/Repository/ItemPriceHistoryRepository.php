<?php

namespace App\Repository;

use App\Model\DynamicDto;
use Doctrine\DBAL\Connection;

class ItemPriceHistoryRepository extends AbstractRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,

    ) {
        parent::__construct($this->connection, $this->dynamicFormFieldRepository);

        $this->baseTable = 'item_price';
    }

    public function getDynamicDto(): DynamicDto
    {
        return new DynamicDto($this->dynamicFormFieldRepository, $this->connection);
    }

    public function getPriceForItem(int $itemId, int $historyId): ?DynamicDto
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from($this->baseTable)
            ->where('item_id = :itemId')
            ->andWhere('id = :historyId')
            ->setParameters([
                'itemId' => $itemId,
                'historyId' => $historyId,
            ])
        ;

        $rawData = $qb->fetchAssociative();
        if ($rawData) {
            $entity = $this->getDynamicDto();
            $entity->setData($rawData);
            return $entity;
        }

        return null;
    }

    public function removeByItemId(int $itemId): void
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->delete($this->baseTable)
            ->where('item_id = :id')
            ->setParameters([
                'id' => $itemId,
            ])
        ;

        $qb->executeQuery();
    }
}