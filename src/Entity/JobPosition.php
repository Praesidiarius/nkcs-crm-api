<?php

namespace App\Entity;

use App\Model\DynamicDto;
use App\Repository\JobPositionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobPositionRepository::class)]
class JobPosition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $itemId = null;

    // do not map item by orm
    private ?DynamicDto $item = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(nullable: false)]
    private ?int $jobId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ItemUnit $unit = null;

    #[ORM\Column(nullable: true)]
    private ?float $amount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItemId(): ?int
    {
        return $this->itemId;
    }

    public function setItemId(?int $itemId): self
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function setItem(?DynamicDto $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getItem(bool $serialized = true): null|array|DynamicDto
    {
        if (!$serialized) {
            return $this->item;
        }

        $this->item?->serializeDataForApiByFormModel('item');
        return $this->item?->getDataSerialized();
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getPriceText(): string
    {
        $posPriceView = $this->getPrice();
        if (fmod($posPriceView, 1) === 0.0) {
            $posPriceView = number_format($this->getPrice(), 0, '.', '\'') . '.-';
        }

        return $posPriceView;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

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

    public function getUnit(): ?ItemUnit
    {
        return $this->unit;
    }

    public function setUnit(?ItemUnit $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getTotal(): float
    {
        return round($this->getAmount() * $this->getPrice(), 2);
    }

    public function getTotalText(): string
    {
        $posTotalView = number_format($this->getTotal(), 2, '.', '\'');
        if (fmod($this->getTotal(), 1) === 0.0) {
            $posTotalView = number_format($this->getTotal(), 0, '.', '\'') . '.-';
        }
        return $posTotalView;
    }
}
