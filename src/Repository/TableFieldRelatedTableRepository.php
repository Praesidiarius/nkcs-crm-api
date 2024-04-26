<?php

namespace App\Repository;

use App\Model\DynamicDto;
use Doctrine\DBAL\Connection;

class TableFieldRelatedTableRepository extends AbstractRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        protected string $baseTable,
        private readonly string $relationField,
    ) {
        parent::__construct($this->connection, $this->dynamicFormFieldRepository);
    }

    public function getDynamicDto(): DynamicDto
    {
        return new DynamicDto($this->dynamicFormFieldRepository, $this->connection);
    }

    public function removeByItemId(int $itemId): void
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->delete($this->baseTable)
            ->where($this->relationField . ' = :id')
            ->setParameters([
                'id' => $itemId,
            ])
        ;

        $qb->executeQuery();
    }
}