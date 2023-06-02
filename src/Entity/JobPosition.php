<?php

namespace App\Entity;

use App\Repository\JobPositionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobPositionRepository::class)]
class JobPosition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?Item $item = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $comment = null;

    #[ORM\ManyToOne(inversedBy: 'jobPositions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Job $job = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?JobPositionUnit $unit = null;

    #[ORM\Column(nullable: true)]
    private ?float $amount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): self
    {
        $this->item = $item;

        return $this;
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

    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function setJob(?Job $job): self
    {
        $this->job = $job;

        return $this;
    }

    public function getUnit(): ?JobPositionUnit
    {
        return $this->unit;
    }

    public function setUnit(?JobPositionUnit $unit): self
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
