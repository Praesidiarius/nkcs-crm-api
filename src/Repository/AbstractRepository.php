<?php

namespace App\Repository;

use App\Model\DynamicDto;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class AbstractRepository
{
    protected string $baseTable = '';

    public function __construct(
        private readonly Connection $connection,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
    ) {
    }

    public function getDynamicDto(): DynamicDto
    {
        return new DynamicDto($this->dynamicFormFieldRepository, $this->connection);
    }

    public function findMostRecent(): ?DynamicDto
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from($this->baseTable)
            ->orderBy('id', 'DESC')
        ;

        $raw = $qb->fetchAssociative();
        if (!$raw) {
            return null;
        }

        $entity = $this->getDynamicDto();
        $entity->setData($raw);

        return $entity;
    }

    public function findAll(?string $table = null): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from($table ?: $this->baseTable)
        ;

        $rawData = $qb->fetchAllAssociative();
        if (count($rawData) > 0) {
            $entities = [];
            foreach ($rawData as $row) {
                $entity = $this->getDynamicDto();
                $entity->setData($row);
                $entities[] = $entity;
            }
            return $entities;
        }

        return [];
    }

    protected function getDtoResults(QueryBuilder $qb): array
    {
        $rawData = $qb->fetchAllAssociative();
        if (count($rawData) > 0) {
            $entities = [];
            foreach ($rawData as $row) {
                $entity = $this->getDynamicDto();
                $entity->setData($row);
                $entities[] = $entity;
            }
            return $entities;
        }

        return [];
    }

    public function findById(int $id): ?DynamicDto
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from($this->baseTable)
            ->where('id = :id')
            ->setParameters([
                'id' => $id,
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

    public function findByAttribute(string $attributeKey, mixed $attributeValue): ?DynamicDto
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from($this->baseTable)
            ->where($attributeKey . ' = :' . $attributeKey)
            ->setParameters([
                $attributeKey => $attributeValue,
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

    public function findAllByAttribute(string $attributeKey, mixed $attributeValue): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from($this->baseTable)
            ->where($attributeKey . ' = :' . $attributeKey)
            ->setParameters([
                $attributeKey => $attributeValue,
            ])
        ;

        $rawData = $qb->fetchAllAssociative();
        if (count($rawData) > 0) {
            $entities = [];
            foreach ($rawData as $row) {
                $entity = $this->getDynamicDto();
                $entity->setData($row);
                $entities[] = $entity;
            }
            return $entities;
        }

        return [];
    }

    public function countByAttribute(string $attributeKey, mixed $attributeValue): int
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('COUNT(id)')
            ->from($this->baseTable)
            ->where($attributeKey . ' = :' . $attributeKey)
            ->setParameters([
                $attributeKey => $attributeValue,
            ])
        ;

        return (int) $qb->fetchOne();
    }

    public function removeById(int $id): void
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->delete($this->baseTable)
            ->where('id = :id')
            ->setParameters([
                'id' => $id,
            ])
        ;

        $qb->executeQuery();
    }


    public function count(): int
    {
        if (!$this->baseTable) {
            return 0;
        }

        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('COUNT(id)')
            ->from($this->baseTable)
        ;

        return (int) $qb->fetchOne();
    }

    public function save(DynamicDto $entity): DynamicDto|string
    {
        $values = [];
        $parameters = [];
        foreach ($entity->getData() as $col => $data) {
            $values[$col] = ':'.$col;
            $parameters[$col] = $data;
        }
        $qb = $this->connection->createQueryBuilder();

        if ($entity->getId()) {
            $parameters['id'] = $entity->getId();
            $qb->update($this->baseTable);
            foreach ($entity->getData() as $col => $data) {
                $values[$col] = ':'.$col;
                $parameters[$col] = is_array($data) ? $data['id'] : $data;
                $qb->set($col, ':' . $col);
            }
            $qb->where('id = :id');
            $qb->setParameters($parameters);

            $qb->executeQuery();

            return $entity;
        }

        $qb->insert($this->baseTable);
        $qb->values($values);
        $qb->setParameters($parameters);

        $qb->executeQuery();

        $entity->setId($this->connection->lastInsertId());

        return $entity;
    }

    public function updateAttribute(string $attributeKey, int|float|string $attributeValue, int $entityId): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->update($this->baseTable)
            ->set($attributeKey, ':' . $attributeKey)
            ->where('id = :id')
            ->setParameters([
                'id' => $entityId,
                $attributeKey => $attributeValue
            ])
        ;

        $qb->executeQuery();
    }
}