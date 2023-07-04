<?php

namespace App\Repository;

use App\Model\DynamicDto;
use Doctrine\DBAL\Connection;

class ContactAddressRepository extends AbstractRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DynamicDto $dynamicEntity,
    ) {
        parent::__construct($this->connection, $this->dynamicEntity);
    }

    public function save(DynamicDto $entity): DynamicDto
    {
        return parent::saveToTable($entity, 'contact_address');
    }

    public function removeByContactId(int $contactId): void
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->delete('contact_address')
            ->where('contact_id = :id')
            ->setParameters([
                'id' => $contactId,
            ])
        ;

        $qb->executeQuery();
    }
}