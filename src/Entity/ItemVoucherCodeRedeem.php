<?php

namespace App\Entity;

use App\Repository\ItemVoucherCodeRedeemRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemVoucherCodeRedeemRepository::class)]
class ItemVoucherCodeRedeem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: false)]
    private ?int $voucherCodeId = null;

    #[ORM\Column(nullable: false)]
    private ?int $jobId = null;

    #[ORM\Column(nullable: false)]
    private ?int $contactId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeInterface $date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVoucherCodeId(): ?int
    {
        return $this->voucherCodeId;
    }

    public function setVoucherCodeId(?int $voucherCodeId): self
    {
        $this->voucherCodeId = $voucherCodeId;

        return $this;
    }

    public function getJobId(): ?int
    {
        return $this->jobId;
    }

    public function setJobId(?int $jobId): self
    {
        $this->jobId = $jobId;

        return $this;
    }

    public function getContactId(): ?int
    {
        return $this->contactId;
    }

    public function setContactId(?int $contactId): self
    {
        $this->contactId = $contactId;

        return $this;
    }

    public function setDate(): self
    {
        $this->date = new DateTimeImmutable();

        return $this;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }
}
