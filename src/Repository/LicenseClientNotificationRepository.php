<?php

namespace App\Repository;

use App\Entity\LicenseClientNotification;
use App\Entity\LicenseClientNotificationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

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
            ->setParameters(new ArrayCollection([
                new Parameter('allClients', 'all'),
                new Parameter('client', $client),
            ]))
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
}
