<?php

namespace App\Repository;

use App\Entity\ContactHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContactHistory>
 *
 * @method ContactHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContactHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContactHistory[]    findAll()
 * @method ContactHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContactHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactHistory::class);
    }

    public function save(ContactHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ContactHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
