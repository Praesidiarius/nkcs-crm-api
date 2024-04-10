<?php

namespace App\Repository;

use App\Entity\LicenseProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LicenseProductRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ItemRepository $itemRepository,
    )
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
        $result = $this->createQueryBuilder('l')
            ->andWhere('l.item IS NOT NULL')
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $licenseProducts = [];
        if (count($result) > 0) {
            foreach ($result as $licenseProduct) {
                $item = $this->itemRepository->findById($licenseProduct->getItem());
                $itemData = $item->getData();
                $licenseProducts[] = [
                    'id' => $licenseProduct->getId(),
                    'item' => [
                        'id' => $itemData['id'],
                        'name' => $itemData['name'],
                        'price' => $itemData['price'],
                        'description' => $itemData['description'],
                        'unit' => $item->getSelectField('unit_id'),
                    ],
                ];
            }
        }

        return $licenseProducts;
    }
}
