<?php

namespace App\Repository;

use App\Model\DynamicDto;
use Cake\Chronos\Chronos;
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

    public function getPricesForItem(int $itemId): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from($this->baseTable)
            ->where('item_id = :itemId')
            ->orderBy('date', 'ASC')
            ->setParameters([
                'itemId' => $itemId,
            ])
        ;

        return $qb->fetchAllAssociative();
    }

    public function getCurrentPriceForItem(int $itemId): float
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from($this->baseTable)
            ->where('item_id = :itemId')
            ->andWhere('date >= :today')
            ->orderBy('date', 'ASC')
            ->setMaxResults(1)
            ->setParameters([
                'itemId' => $itemId,
                'today' => Chronos::now()->format('Y-m-d')
            ])
        ;

        $rawData = $qb->fetchAssociative();
        if ($rawData) {
            return $rawData['price_sell'];
        }

        return 0.0;
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