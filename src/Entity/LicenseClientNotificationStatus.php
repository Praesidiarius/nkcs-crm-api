<?php

namespace App\Entity;

use App\Repository\LicenseClientNotificationStatusRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LicenseClientNotificationStatusRepository::class)]
class LicenseClientNotificationStatus
{
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\Id]
    private ?LicenseClientNotification $notification = null;

    #[ORM\Column(length: 50)]
    #[ORM\Id]
    private ?string $client = null;

    #[ORM\Column(length: 10)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    public function getNotification(): ?LicenseClientNotification
    {
        return $this->notification;
    }

    public function setNotification(?LicenseClientNotification $notification): static
    {
        $this->notification = $notification;

        return $this;
    }

    public function getClient(): ?string
    {
        return $this->client;
    }

    public function setClient(string $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }
}
