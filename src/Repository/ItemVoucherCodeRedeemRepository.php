<?php

namespace App\Repository;

use App\Entity\ItemVoucherCodeRedeem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ItemVoucherCodeRedeem>
 *
 * @method ItemVoucherCodeRedeem|null find($id, $lockMode = null, $lockVersion = null)
 * @method ItemVoucherCodeRedeem|null findOneBy(array $criteria, array $orderBy = null)
 * @method ItemVoucherCodeRedeem[]    findAll()
 * @method ItemVoucherCodeRedeem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
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
