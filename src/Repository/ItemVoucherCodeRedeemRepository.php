<?php

namespace App\Repository;

use App\Entity\ItemVoucherCodeRedeem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ItemVoucherCodeRedeemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemVoucherCodeRedeem::class);
    }

    public function save(ItemVoucherCodeRedeem $entity, bool $flush = false): ItemVoucherCodeRedeem
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $entity;
    }

    public function remove(ItemVoucherCodeRedeem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
