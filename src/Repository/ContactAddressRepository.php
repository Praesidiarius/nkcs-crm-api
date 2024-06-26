<?php

namespace App\Repository;

use App\Model\DynamicDto;
use Doctrine\DBAL\Connection;

class ContactAddressRepository extends AbstractRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,

    ) {
        parent::__construct($this->connection, $this->dynamicFormFieldRepository);

        $this->baseTable = 'contact_address';
    }

    public function getDynamicDto(): DynamicDto
    {
        return new DynamicDto($this->dynamicFormFieldRepository, $this->connection);
    }

    public function getAddressForContact(int $contactId, int $addressId): ?DynamicDto
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from('contact_address')
            ->where('contact_id = :contactId')
            ->andWhere('id = :addressId')
            ->setParameters([
                'contactId' => $contactId,
                'addressId' => $addressId,
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

    public function getPrimaryAddressForContact(int $contactId): ?DynamicDto
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from('contact_address')
            ->where('contact_id = :contactId')
            ->setParameters([
                'contactId' => $contactId,
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