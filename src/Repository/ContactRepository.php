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
    }

    public function getDynamicDto(): DynamicDto
    {
        return new DynamicDto($this->dynamicFormFieldRepository, $this->connection);
    }

    public function findAll(string $table = 'contact'): array
    {
        return parent::findAll('contact');
    }

    public function findMostRecent(string $table = 'contact'): ?DynamicDto
    {
        return parent::findMostRecent('contact');
    }

    public function findById(int $id, string $table = 'contact'): ?DynamicDto
    {
        return parent::findById($id, 'contact');
    }

    public function findByAttribute(string $attributeKey, mixed $attributeValue, string $table = 'contact'): ?DynamicDto
    {
        return parent::findByAttribute($attributeKey, $attributeValue, 'contact');
    }

    public function findByEmail(string $email): ?DynamicDto
    {
        return parent::findByAttribute('email_private', $email, 'contact');
    }

    public function findBySignupToken(string $token): ?DynamicDto
    {
        return parent::findByAttribute('signup_token', $token, 'contact');
    }

    public function removeById(int $id, string $table = 'contact'): void
    {
        $address = $this->addressRepository->findByAttribute('contact_id', $id, 'contact_address');
        parent::removeById($address->getId(), 'contact_address');

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