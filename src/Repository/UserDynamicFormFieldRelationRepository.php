<?php

namespace App\Repository;

use App\Entity\UserDynamicFormFieldRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserDynamicFormFieldRelation>
 *
 * @method UserDynamicFormFieldRelation|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserDynamicFormFieldRelation|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserDynamicFormFieldRelation[]    findAll()
 * @method UserDynamicFormFieldRelation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserDynamicFormFieldRelationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDynamicFormFieldRelation::class);
    }

    public function save(UserDynamicFormFieldRelation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserDynamicFormFieldRelation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return UserDynamicFormFieldRelation[] Returns an array of UserDynamicFormFieldRelation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserDynamicFormFieldRelation
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
