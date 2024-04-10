<?php

namespace App\Repository;

use App\Entity\License;
use Cake\Chronos\Chronos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

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
            ->setParameters(new ArrayCollection([
                new Parameter('holder', $licenseHolder),
                new Parameter('currentDate', Chronos::now()),
            ]))
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
            ->setParameters(new ArrayCollection([
                new Parameter('holder', $licenseHolder),
                new Parameter('currentDate', Chronos::now()),
                new Parameter('activeLicense', $activeLicenseId),
            ]))
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
            ->setParameters(new ArrayCollection([
                new Parameter('holder', $licenseHolder),
                new Parameter('currentDate', Chronos::now()),
            ]))
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
}
