<?php

namespace App\Repository;

use App\Entity\DynamicForm;
use App\Entity\DynamicFormField;
use App\Entity\DynamicFormFieldRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DynamicFormField>
 *
 * @method DynamicFormField|null find($id, $lockMode = null, $lockVersion = null)
 * @method DynamicFormField|null findOneBy(array $criteria, array $orderBy = null)
 * @method DynamicFormField[]    findAll()
 * @method DynamicFormField[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DynamicFormFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DynamicFormField::class);
    }

    public function save(DynamicFormField $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DynamicFormField $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getUserFieldsByFormKey(string $dynamicFormKey): array
    {
        return $this->createQueryBuilder('d')
            ->join(DynamicFormFieldRelation::class, 'dr', Join::WITH,'dr.field = d.id')
            ->join(DynamicForm::class, 'df', Join::WITH, 'd.dynamicForm = df.id')
            ->where('df.formKey = :form')
            ->setParameters([
                'form' => $dynamicFormKey
            ])
            ->orderBy('dr.sortId')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getUserIndexColumnsByFormKey(string $dynamicFormKey): array
    {
        return $this->createQueryBuilder('d')
            ->join(DynamicFormFieldRelation::class, 'dr', Join::WITH,'dr.field = d.id')
            ->join(DynamicForm::class, 'df', Join::WITH, 'd.dynamicForm = df.id')
            ->where('df.formKey = :form')
            ->andWhere('dr.showOnIndex = 1')
            ->setParameters([
                'form' => $dynamicFormKey
            ])
            ->orderBy('dr.sortId')
            ->getQuery()
            ->getResult()
            ;
    }

//    /**
//     * @return DynamicFormField[] Returns an array of DynamicFormField objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?DynamicFormField
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
