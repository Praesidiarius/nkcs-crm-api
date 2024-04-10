<?php

namespace App\Repository;

use App\Entity\Worktime;
use Cake\Chronos\Chronos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

class WorktimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Worktime::class);
    }

    public function save(Worktime $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Worktime $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getWorkTimeCurrentWeek(UserInterface $user): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.user = :user')
            ->andWhere('w.date BETWEEN :start AND :end')
            ->setParameters(new ArrayCollection([
                new Parameter('user', $user),
                new Parameter('start', Chronos::now()->previous(Chronos::MONDAY)),
                new Parameter('end', Chronos::now()->next(Chronos::SUNDAY)),
            ]))
            ->getQuery()
            ->getResult()
        ;
    }
}
