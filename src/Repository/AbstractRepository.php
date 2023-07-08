<?php

namespace App\Repository;

use App\Model\DynamicDto;
use Doctrine\DBAL\Connection;

class AbstractRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DynamicDto $dynamicEntity,
    ) {
    }

    public function findMostRecent(string $table): ?DynamicDto
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from($table)
            ->orderBy('id', 'DESC')
        ;

        $raw = $qb->fetchAssociative();
        if (!$raw) {
            return null;
        }

        $entity = $this->dynamicEntity;
        $this->dynamicEntity->setData($raw);

        return $entity;
    }

    public function findAll(string $table): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from($table)
        ;

        $rawData = $qb->fetchAllAssociative();
        if (count($rawData) > 0) {
            $entities = [];
            foreach ($rawData as $row) {
                $entity = $this->dynamicEntity;
                $this->dynamicEntity->setData($row);
                $entities[] = $entity;
            }
            return $entities;
        }

        return [];
    }

    public function findById(int $id, string $table): ?DynamicDto
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from($table)
            ->where('id = :id')
            ->setParameters([
                'id' => $id,
            ])
        ;

        $rawData = $qb->fetchAssociative();
        if ($rawData) {
            $entity = $this->dynamicEntity;
            $this->dynamicEntity->setData($rawData);
            return $entity;
        }

        return null;
    }

    public function findByAttribute(string $attributeKey, mixed $attributeValue, string $table): ?DynamicDto
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from($table)
            ->where($attributeKey . ' = :' . $attributeKey)
            ->setParameters([
                $attributeKey => $attributeValue,
            ])
        ;

        $rawData = $qb->fetchAssociative();
        if ($rawData) {
            $entity = $this->dynamicEntity;
            $this->dynamicEntity->setData($rawData);
            return $entity;
        }

        return null;
    }

    public function removeById(int $id, string $table): void
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->delete($table)
            ->where('id = :id')
            ->setParameters([
                'id' => $id,
            ])
        ;

        $qb->executeQuery();
    }

    protected function saveToTable(DynamicDto $entity, string $table): DynamicDto|string
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
            $qb->update($table);
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

        $qb->insert($table);
        $qb->values($values);
        $qb->setParameters($parameters);

        $qb->executeQuery();

        $entity->setId($this->connection->lastInsertId());

        return $entity;
    }
}