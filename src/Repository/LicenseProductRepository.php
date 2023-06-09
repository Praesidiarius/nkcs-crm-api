<?php

namespace App\Repository;

use App\Entity\LicenseProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LicenseProduct>
 *
 * @method LicenseProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method LicenseProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method LicenseProduct[]    findAll()
 * @method LicenseProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LicenseProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LicenseProduct::class);
    }

    public function save(LicenseProduct $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LicenseProduct $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findPurchasableLicenseProducts(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.item IS NOT NULL')
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return LicenseProduct[] Returns an array of LicenseProduct objects
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

//    public function findOneBySomeField($value): ?LicenseProduct
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
