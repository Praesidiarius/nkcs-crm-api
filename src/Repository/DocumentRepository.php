<?php

namespace App\Repository;

use App\Entity\Document;
use App\Entity\DocumentType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 *
 * @method Document|null find($id, $lockMode = null, $lockVersion = null)
 * @method Document|null findOneBy(array $criteria, array $orderBy = null)
 * @method Document[]    findAll()
 * @method Document[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    public function save(Document $entity, bool $flush = false): Document
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        $this->getEntityManager()->refresh($entity);

        return $entity;
    }

    public function remove(Document $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findBySearchAttributes(int $page, int $pageSize, ?string $documentType): Paginator
    {
        $qb = $this->createQueryBuilder('d')
            ->orderBy('d.id', 'ASC')
            ->setFirstResult(($page-1) * $pageSize)
            ->setMaxResults($pageSize)
        ;

        if ($documentType) {
            $qb
                ->join(DocumentType::class, 'dt', JOIN::WITH, 'dt.id = d.type')
                ->andWhere('dt.identifier = :documentType')
                ->setParameter('documentType', $documentType)
            ;
        }

        return new Paginator($qb->getQuery(), false);
    }
}
