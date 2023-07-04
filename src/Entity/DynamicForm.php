<?php

namespace App\Entity;

use App\Repository\DynamicFormRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DynamicFormRepository::class)]
class DynamicForm
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(length: 100)]
    private ?string $formKey = null;

    #[ORM\OneToMany(mappedBy: 'dynamicForm', targetEntity: DynamicFormField::class, orphanRemoval: true)]
    private Collection $fields;

    #[ORM\OneToMany(mappedBy: 'form', targetEntity: DynamicFormSection::class, orphanRemoval: true)]
    private Collection $dynamicFormSections;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->dynamicFormSections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getFormKey(): ?string
    {
        return $this->formKey;
    }

    public function setFormKey(string $formKey): static
    {
        $this->formKey = $formKey;

        return $this;
    }

    /**
     * @return Collection<int, DynamicFormField>
     */
    public function getFields(): Collection
    {
        $mainFields = new ArrayCollection();
        foreach ($this->fields as $field) {
            if (!$field->getParentField()) {
                $mainFields->add($field);
            }
        }
        return $mainFields;
    }

    public function addField(DynamicFormField $field): static
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
            $field->setDynamicForm($this);
        }

        return $this;
    }

    public function removeField(DynamicFormField $field): static
    {
        if ($this->fields->removeElement($field)) {
            // set the owning side to null (unless already changed)
            if ($field->getDynamicForm() === $this) {
                $field->setDynamicForm(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DynamicFormSection>
     */
    public function getDynamicFormSections(): Collection
    {
        return $this->dynamicFormSections;
    }

    public function addDynamicFormSection(DynamicFormSection $dynamicFormSection): static
    {
        if (!$this->dynamicFormSections->contains($dynamicFormSection)) {
            $this->dynamicFormSections->add($dynamicFormSection);
            $dynamicFormSection->setForm($this);
        }

        return $this;
    }

    public function removeDynamicFormSection(DynamicFormSection $dynamicFormSection): static
    {
        if ($this->dynamicFormSections->removeElement($dynamicFormSection)) {
            // set the owning side to null (unless already changed)
            if ($dynamicFormSection->getForm() === $this) {
                $dynamicFormSection->setForm(null);
            }
        }

        return $this;
    }
}
