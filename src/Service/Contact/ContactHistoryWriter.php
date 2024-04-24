<?php

namespace App\Service\Contact;

use App\Entity\ContactHistory;
use App\Repository\ContactHistoryEventRepository;
use App\Repository\ContactHistoryRepository;
use Cake\Chronos\Chronos;
use Symfony\Bundle\SecurityBundle\Security;

readonly class ContactHistoryWriter
{
    public function __construct(
        private ContactHistoryEventRepository $eventRepository,
        private ContactHistoryRepository $historyRepository,
        private Security $security,
    ) {
    }

    public function write(string $eventName, int $contactId, ?string $comment = null): void
    {
        $event = $this->eventRepository->findOneBy(['name' => $eventName]);
        if (!$event) {
            return;
        }

        $historyEntry = (new ContactHistory())
            ->setEvent($event)
            ->setContactId($contactId)
            ->setComment($comment)
            ->setCreatedBy($this->security->getUser())
            ->setCreatedAt(Chronos::now()->toNative())
            ->setDate(Chronos::now()->toNative())
        ;

        $this->historyRepository->save($historyEntry, true);
    }
}