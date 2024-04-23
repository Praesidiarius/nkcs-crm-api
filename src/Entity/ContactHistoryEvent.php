<?php

namespace App\Entity;

use App\Repository\ContactHistoryEventRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ContactHistoryEventRepository::class)]
class ContactHistoryEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['history:list', 'history:events:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['history:list'])]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $selectable = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isSelectable(): ?bool
    {
        return $this->selectable;
    }

    public function setSelectable(bool $selectable): static
    {
        $this->selectable = $selectable;

        return $this;
    }

    #[Groups(['history:events:list'])]
    public function getText(): ?string
    {
        return $this->name;
    }
}
