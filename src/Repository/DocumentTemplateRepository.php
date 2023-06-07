<?php

namespace App\Repository;

use App\Entity\DocumentTemplate;
use App\Entity\DocumentType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentTemplate>
 *
 * @method DocumentTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentTemplate[]    findAll()
 * @method DocumentTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentTemplate::class);
    }

    public function save(DocumentTemplate $entity, bool $flush = false): DocumentTemplate
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $entity;
    }

    public function remove(DocumentTemplate $entity, bool $flush = false): void
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

//    /**
//     * @return DocumentTemplate[] Returns an array of DocumentTemplate objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?DocumentTemplate
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
