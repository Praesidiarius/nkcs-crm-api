<?php

namespace App\Repository;

use App\Entity\ItemVoucherCode;
use App\Entity\ItemVoucherCodeRedeem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

class ItemVoucherCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemVoucherCode::class);
    }

    public function save(ItemVoucherCode $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ItemVoucherCode $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllUsagesByJobId(int $jobId): array
    {
        $qb = $this->createQueryBuilder('v');

        $qb
            ->leftJoin(
                ItemVoucherCodeRedeem::class,
                'vcr',
                Join::WITH,
                'vcr.voucherCodeId = v.id'
            )
            ->where('vcr.jobId = :jobId')
            ->setParameters(new ArrayCollection([
                new Parameter('jobId', $jobId),
            ]))
        ;

        return $qb->getQuery()->getResult();
    }
}
