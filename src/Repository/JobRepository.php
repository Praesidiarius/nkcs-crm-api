<?php

namespace App\Repository;

use App\Model\DynamicDto;
use App\Model\JobDto;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

class JobRepository extends AbstractRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly TranslatorInterface $translator,
        private readonly JobPositionRepository $jobPositionRepository,
        private readonly ItemRepository $itemRepository,
    ) {
        parent::__construct($this->connection, $this->dynamicFormFieldRepository);
    }

    public function getDynamicDto(): DynamicDto
    {
        return new JobDto(
            $this->dynamicFormFieldRepository,
            $this->connection,
            $this->translator,
            $this->jobPositionRepository,
            $this->itemRepository,
        );
    }

    public function findAll(string $table = 'job'): array
    {
        return parent::findAll('job');
    }

    public function findMostRecent(string $table = 'job'): ?DynamicDto
    {
        return parent::findMostRecent('job');
    }

    public function findById(int $id, string $table = 'job'): ?DynamicDto
    {
        return parent::findById($id, 'job');
    }

    public function findByAttribute(string $attributeKey, mixed $attributeValue, string $table = 'job'): ?DynamicDto
    {
        return parent::findByAttribute($attributeKey, $attributeValue, 'job');
    }

    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('*')
            ->from('job', 'j')
            ->where('j.created_date BETWEEN :dateFrom AND :dateTo')
            ->setParameters([
                'dateFrom' => $from->format('Y-m-d') . ' 00:00:00',
                'dateTo' => $to->format('Y-m-d') . ' 23:59:59'
            ])
        ;

        return $this->getDtoResults($qb);
    }

    public function removeById(int $id, string $table = 'job'): void
    {
        parent::removeById($id, 'job');
    }

    public function findBySearchAttributes(int $page, int $pageSize): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('job', 'j')
            ->orderBy('j.id', 'ASC')
            ->setFirstResult(($page-1) * $pageSize)
            ->setMaxResults($pageSize)
        ;

        return $qb->fetchAllAssociative();
    }

    public function save(DynamicDto $entity): DynamicDto|string
    {
        return parent::saveToTable($entity, 'job');
    }
}
