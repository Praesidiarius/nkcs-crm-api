<?php

namespace App\Repository;

use App\Entity\DynamicForm;
use App\Entity\DynamicFormField;
use App\Entity\DynamicFormFieldRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

class DynamicFormFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DynamicFormField::class);
    }

    public function save(DynamicFormField $entity, bool $flush = false): DynamicFormField
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $entity;
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
            ->setParameters(new ArrayCollection([
                new Parameter('form', $dynamicFormKey),
            ]))
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
            ->setParameters(new ArrayCollection([
                new Parameter('form', $dynamicFormKey),
            ]))
            ->orderBy('dr.sortId')
            ->getQuery()
            ->getResult()
        ;
    }
}
