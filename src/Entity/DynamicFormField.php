<?php

namespace App\Entity;

use App\Repository\DynamicFormFieldRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DynamicFormFieldRepository::class)]
class DynamicFormField
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['field:basic'])]
    private ?string $label = null;

    #[ORM\Column(length: 100)]
    #[Groups(['field:basic'])]
    private ?string $fieldKey = null;

    #[ORM\Column(length: 25)]
    #[Groups(['field:basic'])]
    private ?string $fieldType = null;

    #[ORM\Column]
    #[Groups(['field:basic'])]
    private bool $fieldRequired = false;

    #[ORM\Column]
    #[Groups(['field:basic'])]
    private ?int $columns = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['field:basic'])]
    private ?string $defaultData = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $relatedTable = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $relatedTableCol = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'nestedFields')]
    private ?self $parentField = null;

    #[ORM\OneToMany(mappedBy: 'parentField', targetEntity: self::class)]
    private Collection $nestedFields;

    #[ORM\ManyToOne(inversedBy: 'formFields')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DynamicFormSection $section = null;

    #[ORM\ManyToOne(inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false)]
    #[Ignore]
    private ?DynamicForm $dynamicForm = null;

    public function __construct()
    {
        $this->nestedFields = new ArrayCollection();
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

    public function getFieldKey(): ?string
    {
        return $this->fieldKey;
    }

    public function setFieldKey(string $fieldKey): static
    {
        $this->fieldKey = $fieldKey;

        return $this;
    }

    public function getFieldType(): ?string
    {
        return $this->fieldType;
    }

    public function setFieldType(string $fieldType): static
    {
        $this->fieldType = $fieldType;

        return $this;
    }

    public function setFieldRequired(bool $required): self
    {
        $this->fieldRequired = $required;

        return $this;
    }

    public function isFieldRequired(): bool
    {
        return $this->fieldRequired;
    }

    public function getColumns(): ?int
    {
        return $this->columns;
    }

    public function setColumns(int $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function getDefaultData(): ?string
    {
        return $this->defaultData;
    }

    public function setDefaultData(?string $defaultData): static
    {
        $this->defaultData = $defaultData;

        return $this;
    }

    public function getParentField(): ?self
    {
        return $this->parentField;
    }

    public function setParentField(?self $parentField): static
    {
        $this->parentField = $parentField;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getNestedFields(): Collection
    {
        return $this->nestedFields;
    }

    public function addNestedField(self $nestedField): static
    {
        if (!$this->nestedFields->contains($nestedField)) {
            $this->nestedFields->add($nestedField);
            $nestedField->setParentField($this);
        }

        return $this;
    }

    public function removeNestedField(self $nestedField): static
    {
        if ($this->nestedFields->removeElement($nestedField)) {
            // set the owning side to null (unless already changed)
            if ($nestedField->getParentField() === $this) {
                $nestedField->setParentField(null);
            }
        }

        return $this;
    }

    public function getSection(): ?DynamicFormSection
    {
        return $this->section;
    }

    public function setSection(?DynamicFormSection $section): static
    {
        $this->section = $section;

        return $this;
    }

    public function getRelatedTable(): ?string
    {
        return $this->relatedTable;
    }

    public function setRelatedTable(?string $relatedTable): static
    {
        $this->relatedTable = $relatedTable;

        return $this;
    }

    public function getRelatedTableCol(): ?string
    {
        return $this->relatedTableCol;
    }

    public function setRelatedTableCol(?string $relatedTableCol): static
    {
        $this->relatedTableCol = $relatedTableCol;

        return $this;
    }

    public function setDynamicForm(DynamicForm $form): self
    {
        $this->dynamicForm = $form;

        return $this;
    }
}
