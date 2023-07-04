<?php

namespace App\Entity;

use App\Repository\DynamicFormFieldRelationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DynamicFormFieldRelationRepository::class)]
class DynamicFormFieldRelation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?DynamicFormField $field = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?int $sortId = null;

    #[ORM\Column]
    private bool $showOnIndex = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getField(): ?DynamicFormField
    {
        return $this->field;
    }

    public function setField(?DynamicFormField $field): static
    {
        $this->field = $field;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getSortId(): ?int
    {
        return $this->sortId;
    }

    public function setSortId(int $sortId): static
    {
        $this->sortId = $sortId;

        return $this;
    }

    public function getShowOnIndex(): bool
    {
        return $this->showOnIndex;
    }

    public function setShowOnIndex(bool $show): static
    {
        $this->showOnIndex = $show;

        return $this;
    }
}
