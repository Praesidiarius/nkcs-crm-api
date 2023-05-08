<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column]
    private bool $isCompany = false;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email]
    #[Assert\Unique]
    private ?string $emailPrivate = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email]
    #[Assert\Unique]
    private ?string $emailBusiness = null;

    #[ORM\Column]
    private ?int $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $createdDate = null;

    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: ContactAddress::class)]
    private Collection $address;

    public function __construct()
    {
        $this->address = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function isIsCompany(): ?bool
    {
        return $this->isCompany;
    }

    public function setIsCompany(bool $isCompany): self
    {
        $this->isCompany = $isCompany;

        return $this;
    }

    public function getEmailPrivate(): ?string
    {
        return $this->emailPrivate;
    }

    public function setEmailPrivate(?string $emailPrivate): self
    {
        $this->emailPrivate = $emailPrivate;

        return $this;
    }

    public function getEmailBusiness(): ?string
    {
        return $this->emailBusiness;
    }

    public function setEmailBusiness(string $emailBusiness): self
    {
        $this->emailBusiness = $emailBusiness;

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

    /**
     * @return Collection<int, ContactAddress>
     */
    public function getAddress(): Collection
    {
        return $this->address;
    }

    public function addAddress(ContactAddress $address): self
    {
        if (!$this->address->contains($address)) {
            $this->address->add($address);
            $address->setContact($this);
        }

        return $this;
    }

    public function removeAddress(ContactAddress $address): self
    {
        if ($this->address->removeElement($address)) {
            // set the owning side to null (unless already changed)
            if ($address->getContact() === $this) {
                $address->setContact(null);
            }
        }

        return $this;
    }
}
