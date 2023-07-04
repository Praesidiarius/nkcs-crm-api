<?php

namespace App\Repository;

use App\Model\DynamicDto;
use Doctrine\DBAL\Connection;

class ItemRepository extends AbstractRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DynamicDto $dynamicEntity,
    ) {
        parent::__construct($this->connection, $this->dynamicEntity);
    }

    public function findById(int $id, string $table = 'item'): ?DynamicDto
    {
        return parent::findById($id, 'item');
    }

    public function removeById(int $id, string $table = 'item'): void
    {
        parent::removeById($id, 'item');
    }

    public function findBySearchAttributes(int $page, int $pageSize): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('item', 'i')
            ->orderBy('i.id', 'ASC')
            ->setFirstResult(($page-1) * $pageSize)
            ->setMaxResults($pageSize)
        ;

        return $qb->fetchAllAssociative();
    }

    public function save(DynamicDto $entity): DynamicDto|string
    {
        return parent::saveToTable($entity, 'item');
    }
}
