<?php

namespace App\Repository;

use App\Entity\LicensePurchase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LicensePurchase>
 *
 * @method LicensePurchase|null find($id, $lockMode = null, $lockVersion = null)
 * @method LicensePurchase|null findOneBy(array $criteria, array $orderBy = null)
 * @method LicensePurchase[]    findAll()
 * @method LicensePurchase[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LicensePurchaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LicensePurchase::class);
    }

    public function save(LicensePurchase $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LicensePurchase $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return LicensePurchase[] Returns an array of LicensePurchase objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?LicensePurchase
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
