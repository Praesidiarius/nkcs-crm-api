<?php

namespace App\Repository;

use App\Entity\SystemSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SystemSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SystemSetting::class);
    }

    public function save(SystemSetting $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SystemSetting $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findSettingByKey($settingKey): ?SystemSetting
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.settingKey = :val')
            ->setParameter('val', $settingKey)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
