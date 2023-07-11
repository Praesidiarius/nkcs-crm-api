<?php

namespace App\Repository;

use App\Entity\LicenseProduct;
use App\Model\DynamicDto;
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
