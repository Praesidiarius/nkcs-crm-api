<?php

namespace App\Entity;

use App\Model\DynamicDto;
use App\Repository\ItemVoucherCodeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ItemVoucherCodeRepository::class)]
class ItemVoucherCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $itemId = null;

    // do not map item by orm
    private ?DynamicDto $item = null;

    #[ORM\Column(nullable: true)]
    private ?int $voucherId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?JobPosition $position = null;

    #[ORM\Column(nullable: false)]
    private string $code = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItemId(): ?int
    {
        return $this->itemId;
    }

    public function setItemId(?int $itemId): self
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function setItem(?DynamicDto $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getItem(bool $serialized = true): null|array|DynamicDto
    {
        if (!$serialized) {
            return $this->item;
        }

        $this->item?->serializeDataForApiByFormModel('item');
        return $this->item?->getDataSerialized();
    }

    public function setVoucherId(?int $voucherId): self
    {
        $this->voucherId = $voucherId;

        return $this;
    }

    public function getVoucherId(): ?int
    {
        return $this->voucherId;
    }

    public function setJobPosition(JobPosition $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getJobPosition(): ?JobPosition
    {
        return $this->position;
    }

    public function generateCode(): self
    {
        $this->code = Uuid::v4();

        return $this;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
