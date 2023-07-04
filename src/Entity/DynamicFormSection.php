<?php

namespace App\Entity;

use App\Repository\DynamicFormSectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: DynamicFormSectionRepository::class)]
class DynamicFormSection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $sectionLabel = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'nestedSections')]
    private ?self $parentSection = null;

    #[ORM\OneToMany(mappedBy: 'parentSection', targetEntity: self::class)]
    #[Ignore]
    private Collection $nestedSections;

    #[ORM\Column(length: 150)]
    private ?string $sectionKey = null;

    #[ORM\ManyToOne(inversedBy: 'dynamicFormSections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DynamicForm $form = null;

    public function __construct()
    {
        $this->nestedSections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSectionLabel(): ?string
    {
        return $this->sectionLabel;
    }

    public function setSectionLabel(?string $sectionLabel): static
    {
        $this->sectionLabel = $sectionLabel;

        return $this;
    }

    public function getParentSection(): ?self
    {
        return $this->parentSection;
    }

    public function setParentSection(?self $parentSection): static
    {
        $this->parentSection = $parentSection;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getNestedSections(): Collection
    {
        return $this->nestedSections;
    }

    public function addNestedSection(self $nestedSection): static
    {
        if (!$this->nestedSections->contains($nestedSection)) {
            $this->nestedSections->add($nestedSection);
            $nestedSection->setParentSection($this);
        }

        return $this;
    }

    public function removeNestedSection(self $nestedSection): static
    {
        if ($this->nestedSections->removeElement($nestedSection)) {
            // set the owning side to null (unless already changed)
            if ($nestedSection->getParentSection() === $this) {
                $nestedSection->setParentSection(null);
            }
        }

        return $this;
    }

    public function getSectionKey(): ?string
    {
        return $this->sectionKey;
    }

    public function setSectionKey(string $sectionKey): static
    {
        $this->sectionKey = $sectionKey;

        return $this;
    }

    public function getForm(): ?DynamicForm
    {
        return $this->form;
    }

    public function setForm(?DynamicForm $form): static
    {
        $this->form = $form;

        return $this;
    }
}
