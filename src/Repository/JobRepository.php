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

    public function findById(int $id, string $table = 'job'): ?DynamicDto
    {
        return parent::findById($id, 'job');
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
