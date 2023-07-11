<?php

namespace App\Repository;

use App\Entity\LicenseClientNotificationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LicenseClientNotificationStatus>
 *
 * @method LicenseClientNotificationStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method LicenseClientNotificationStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method LicenseClientNotificationStatus[]    findAll()
 * @method LicenseClientNotificationStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LicenseClientNotificationStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LicenseClientNotificationStatus::class);
    }

    public function save(LicenseClientNotificationStatus $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LicenseClientNotificationStatus $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return LicenseClientNotificationStatus[] Returns an array of LicenseClientNotificationStatus objects
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

//    public function findOneBySomeField($value): ?LicenseClientNotificationStatus
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
