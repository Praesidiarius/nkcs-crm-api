<?php

namespace App\Dto;

use DateTimeImmutable;

readonly class Base64FileRequest
{
    public function __construct(
        private string $file,
        private string $name,
        private string $extension,
        private string $mimeType,
    ) {
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }
}