<?php

namespace App\Repository;

use App\Entity\License;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<License>
 *
 * @method License|null find($id, $lockMode = null, $lockVersion = null)
 * @method License|null findOneBy(array $criteria, array $orderBy = null)
 * @method License[]    findAll()
 * @method License[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LicenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, License::class);
    }

    public function save(License $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActiveLicense(string $licenseHolder): ?License
    {
        $qb = $this->createQueryBuilder('l');

        $qb
            ->where('l.holder = :holder')
            ->andWhere('l.dateValid >= :currentDate')
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(1)
            ->setParameters([
                'holder' => $licenseHolder,
                'currentDate' => new \DateTime(),
            ])
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findFutureLicenses(string $licenseHolder, int $activeLicenseId): array
    {
        $qb = $this->createQueryBuilder('l');

        $qb
            ->where('l.holder = :holder')
            ->andWhere('l.dateValid >= :currentDate')
            ->andWhere('l.id != :activeLicense')
            ->orderBy('l.id', 'ASC')
            ->setParameters([
                'holder' => $licenseHolder,
                'currentDate' => new \DateTime(),
                'activeLicense' => $activeLicenseId,
            ])
        ;

        return $qb->getQuery()->getResult() ?? [];
    }

    public function findArchivedLicenses(string $licenseHolder): array
    {
        $qb = $this->createQueryBuilder('l');

        $qb
            ->where('l.holder = :holder')
            ->andWhere('l.dateValid < :currentDate')
            ->orderBy('l.id', 'ASC')
            ->setParameters([
                'holder' => $licenseHolder,
                'currentDate' => new \DateTime(),
            ])
        ;

        return $qb->getQuery()->getResult() ?? [];
    }

    public function remove(License $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return License[] Returns an array of License objects
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

//    public function findOneBySomeField($value): ?License
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
