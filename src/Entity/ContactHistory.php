<?php

namespace App\Entity;

use App\Repository\ContactHistoryRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ContactHistoryRepository::class)]
class ContactHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['history:list'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['history:list'])]
    private ?ContactHistoryEvent $event = null;

    #[ORM\Column]
    private ?int $contactId = null;

    #[ORM\Column]
    #[Groups(['history:list'])]
    private ?DateTimeImmutable $date = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $dateReminder = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['history:list'])]
    private ?string $comment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'created_by', nullable: false)]
    #[Groups(['history:list'])]
    private ?User $createdBy = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?ContactHistoryEvent
    {
        return $this->event;
    }

    public function setEvent(?ContactHistoryEvent $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getContactId(): ?int
    {
        return $this->contactId;
    }

    public function setContactId(int $contactId): static
    {
        $this->contactId = $contactId;

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

    public function getDateReminder(): ?\DateTimeInterface
    {
        return $this->dateReminder;
    }

    public function setDateReminder(?\DateTimeInterface $dateReminder): static
    {
        $this->dateReminder = $dateReminder;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
