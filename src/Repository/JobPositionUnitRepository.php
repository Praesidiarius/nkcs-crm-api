<?php

namespace App\Repository;

use App\Entity\JobPositionUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobPositionUnit>
 *
 * @method JobPositionUnit|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobPositionUnit|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobPositionUnit[]    findAll()
 * @method JobPositionUnit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobPositionUnitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobPositionUnit::class);
    }

    public function save(JobPositionUnit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(JobPositionUnit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return JobPositionUnit[] Returns an array of JobPositionUnit objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('j.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?JobPositionUnit
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
