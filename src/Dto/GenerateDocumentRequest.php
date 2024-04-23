<?php

namespace App\Dto;

readonly class GenerateDocumentRequest
{
    public function __construct(
        private int $documentId,
        private int $entityId,
        private string $entityType,
        private ?int $addressId = null,
    ) {
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getAddressId(): ?int
    {
        return $this->addressId;
    }
}