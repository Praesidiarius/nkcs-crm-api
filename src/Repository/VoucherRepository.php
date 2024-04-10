<?php

namespace App\Repository;

use App\Model\DynamicDto;
use App\Model\VoucherDto;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

class VoucherRepository extends AbstractRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($this->connection, $this->dynamicFormFieldRepository);

        $this->baseTable = 'item_voucher';
    }

    public function getDynamicDto(): DynamicDto
    {
        return new VoucherDto($this->dynamicFormFieldRepository, $this->connection, $this->translator);
    }

    public function findBySearchAttributes(int $page, int $pageSize): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->baseTable, 'v')
            ->orderBy('v.id', 'ASC')
            ->setFirstResult(($page-1) * $pageSize)
            ->setMaxResults($pageSize)
        ;

        return $qb->fetchAllAssociative();
    }
}
