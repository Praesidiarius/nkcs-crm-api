<?php

namespace App\Entity;

use App\Repository\LicenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LicenseRepository::class)]
class License
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $holder = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreated = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValid = null;

    #[ORM\ManyToOne(inversedBy: 'licenses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contact $contact = null;

    #[ORM\Column(length: 255)]
    private ?string $urlApi = null;

    #[ORM\Column(length: 255)]
    private ?string $urlClient = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?LicenseProduct $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHolder(): ?string
    {
        return $this->holder;
    }

    public function setHolder(string $holder): self
    {
        $this->holder = $holder;

        return $this;
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

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateValid(): ?\DateTimeInterface
    {
        return $this->dateValid;
    }

    public function setDateValid(?\DateTimeInterface $dateValid): self
    {
        $this->dateValid = $dateValid;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    public function getUrlApi(): ?string
    {
        return $this->urlApi;
    }

    public function setUrlApi(string $urlApi): self
    {
        $this->urlApi = $urlApi;

        return $this;
    }

    public function getUrlClient(): ?string
    {
        return $this->urlClient;
    }

    public function setUrlClient(string $urlClient): self
    {
        $this->urlClient = $urlClient;

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

    public function isValid(): bool
    {
        if ($this->getDateValid()) {
            if ($this->getDateValid() >= new \DateTime()) {
                return true;
            }

            return false;
        }

        // if there is no valid date, it is a lifetime license
        return true;
    }

    public function getProduct(): ?LicenseProduct
    {
        return $this->product;
    }

    public function setProduct(?LicenseProduct $product): self
    {
        $this->product = $product;

        return $this;
    }
}
