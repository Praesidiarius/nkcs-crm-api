<?php

namespace App\Repository;

use App\Model\DynamicDto;
use Doctrine\DBAL\Connection;

class ContactRepository extends AbstractRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContactAddressRepository $addressRepository,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
    ) {
        parent::__construct($this->connection, $this->dynamicFormFieldRepository);

        $this->baseTable = 'contact';
    }

    public function getDynamicDto(): DynamicDto
    {
        return new DynamicDto($this->dynamicFormFieldRepository, $this->connection);
    }

    public function findByEmail(string $email): ?DynamicDto
    {
        return parent::findByAttribute('email_private', $email);
    }

    public function findBySignupToken(string $token): ?DynamicDto
    {
        return parent::findByAttribute('signup_token', $token);
    }

    public function removeById(int $id, string $table = 'contact'): void
    {
        $this->addressRepository->removeByContactId($id);

        parent::removeById($id);
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
}