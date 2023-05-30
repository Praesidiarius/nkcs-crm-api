<?php

namespace App\Entity;

use App\Repository\JobRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: JobRepository::class)]
class Job
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $title = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?JobType $type = null;

    #[ORM\OneToMany(mappedBy: 'job', targetEntity: JobPosition::class, orphanRemoval: true)]
    #[Ignore]
    private Collection $jobPositions;

    #[ORM\Column]
    private ?int $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeInterface $createdDate = null;

    public function __construct()
    {
        $this->jobPositions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getType(): ?JobType
    {
        return $this->type;
    }

    public function setType(?JobType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, JobPosition>
     */
    public function getJobPositions(): Collection
    {
        return $this->jobPositions;
    }

    public function addJobPosition(JobPosition $jobPosition): self
    {
        if (!$this->jobPositions->contains($jobPosition)) {
            $this->jobPositions->add($jobPosition);
            $jobPosition->setJob($this);
        }

        return $this;
    }

    public function removeJobPosition(JobPosition $jobPosition): self
    {
        if ($this->jobPositions->removeElement($jobPosition)) {
            // set the owning side to null (unless already changed)
            if ($jobPosition->getJob() === $this) {
                $jobPosition->setJob(null);
            }
        }

        return $this;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(int $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedDate(): ?DateTimeInterface
    {
        return $this->createdDate;
    }

    public function setCreatedDate(DateTimeInterface $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }
}