<?php

namespace App\Repository;

use App\Model\DynamicDto;
use Doctrine\DBAL\Connection;

class ItemRepository extends AbstractRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
    ) {
        parent::__construct($this->connection, $this->dynamicFormFieldRepository);

        $this->baseTable = 'item';
    }

    public function getDynamicDto(): DynamicDto
    {
        return new DynamicDto($this->dynamicFormFieldRepository, $this->connection);
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
}
