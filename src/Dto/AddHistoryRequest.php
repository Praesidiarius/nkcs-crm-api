<?php

namespace App\Dto;

use DateTimeImmutable;

readonly class AddHistoryRequest
{
    public function __construct(
        private int $eventId,
        private DateTimeImmutable $date,
        private ?string $comment = null
    ) {
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
}