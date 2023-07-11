<?php

namespace App\Entity;

use App\Repository\LicenseProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LicenseProductRepository::class)]
class LicenseProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'item_id')]
    private ?int $item = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItem(): ?int
    {
        return $this->item;
    }

    public function setItem(?int $item): self
    {
        $this->item = $item;

        return $this;
    }
}
