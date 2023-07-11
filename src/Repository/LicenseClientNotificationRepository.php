<?php

namespace App\Repository;

use App\Entity\LicenseClientNotification;
use App\Entity\LicenseClientNotificationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LicenseClientNotification>
 *
 * @method LicenseClientNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method LicenseClientNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method LicenseClientNotification[]    findAll()
 * @method LicenseClientNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LicenseClientNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LicenseClientNotification::class);
    }

    public function getOpenNotifications(string $client): array
    {
        $qb = $this->createQueryBuilder('l');

        $qb
            ->select('l as notification', 'ln.status as status')
            ->where('l.client = :allClients OR l.client = :client')
            ->leftJoin(LicenseClientNotificationStatus::class, 'ln', JOIN::WITH, 'ln.notification = l.id')
            ->setParameters([
                'allClients' => 'all',
                'client' => $client
            ])
            ->orderBy('l.date', 'DESC')
        ;

        $notificationsWithStatus = $qb->getQuery()->getResult();
        $notificationsCleaned = [];
        foreach ($notificationsWithStatus as $notificationsWithStatus) {
            if ($notificationsWithStatus['status'] === null) {
                $notificationsCleaned[] = $notificationsWithStatus['notification'];
            }
        }

        return $notificationsCleaned;
    }

    public function save(LicenseClientNotification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LicenseClientNotification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return LicenseClientNotification[] Returns an array of LicenseClientNotification objects
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

//    public function findOneBySomeField($value): ?LicenseClientNotification
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
