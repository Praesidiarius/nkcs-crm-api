<?php

namespace App\Repository;

use App\Model\DynamicDto;
use Doctrine\DBAL\Connection;

class ContactRepository extends AbstractRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DynamicDto $dynamicEntity,
    ) {
        parent::__construct($this->connection, $this->dynamicEntity);
    }

    public function findById(int $id, string $table = 'contact'): ?DynamicDto
    {
        return parent::findById($id, 'contact');
    }

    public function removeById(int $id, string $table = 'contact'): void
    {
        parent::removeById($id, 'contact');
    }

    public function isEmailAddressAlreadyInUse(string $email): bool
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('id')
            ->from('contact')
            ->where('email_private LIKE :email')
            ->setParameter('email', $email)
        ;
        return count($qb->fetchAllAssociative()) > 0;
    }

    public function findBySearchAttributes(int $page, int $pageSize): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('contact', 'c')
            ->orderBy('c.id', 'ASC')
            ->setFirstResult(($page-1) * $pageSize)
            ->setMaxResults($pageSize)
        ;

        return $qb->fetchAllAssociative();
    }

    public function save(DynamicDto $entity): DynamicDto|string
    {
        return parent::saveToTable($entity, 'contact');
    }
}