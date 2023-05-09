<?php

namespace App\Entity;

use App\Repository\JobTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobTypeRepository::class)]
class JobType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $typeKey = null;

    #[ORM\Column(length: 100)]
    private ?string $typeValue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeKey(): ?string
    {
        return $this->typeKey;
    }

    public function setTypeKey(string $typeKey): self
    {
        $this->typeKey = $typeKey;

        return $this;
    }

    public function getTypeValue(): ?string
    {
        return $this->typeValue;
    }

    public function setTypeValue(string $typeValue): self
    {
        $this->typeValue = $typeValue;

        return $this;
    }
}
