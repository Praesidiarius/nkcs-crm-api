<?php

namespace App\Repository;

use App\Model\DynamicDto;
use App\Model\JobDto;
use App\Service\ChartDataGenerator;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

class JobRepository extends AbstractRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly TranslatorInterface $translator,
        private readonly JobPositionRepository $jobPositionRepository,
        private readonly ItemRepository $itemRepository,
        private readonly ItemTypeRepository $itemTypeRepository,
        private readonly ItemVoucherCodeRepository $voucherCodeRepository,
        private readonly VoucherRepository $voucherRepository,
        private readonly SystemSettingRepository $systemSettings,
        private readonly ChartDataGenerator $chartDataGenerator,
    ) {
        parent::__construct($this->connection, $this->dynamicFormFieldRepository);

        $this->baseTable = 'job';
    }

    public function getDynamicDto(): DynamicDto
    {
        return new JobDto(
            $this->dynamicFormFieldRepository,
            $this->connection,
            $this->translator,
            $this->jobPositionRepository,
            $this->itemTypeRepository,
            $this->itemRepository,
            $this->voucherCodeRepository,
            $this->voucherRepository,
            $this->systemSettings,
            $this->chartDataGenerator,
        );
    }

    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('*')
            ->from('job', 'j')
            ->where('j.created_date BETWEEN :dateFrom AND :dateTo')
            ->setParameters([
                'dateFrom' => $from->format('Y-m-d') . ' 00:00:00',
                'dateTo' => $to->format('Y-m-d') . ' 23:59:59'
            ])
        ;

        return $this->getDtoResults($qb);
    }

    public function findBySearchAttributes(int $page, int $pageSize): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('job', 'j')
            ->orderBy('j.id', 'ASC')
            ->setFirstResult(($page-1) * $pageSize)
            ->setMaxResults($pageSize)
        ;

        return $qb->fetchAllAssociative();
    }
}
