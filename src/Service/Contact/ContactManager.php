<?php

namespace App\Service\Contact;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ContactManager
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    )
    {
    }

    public function getIndexHeaders(): array
    {
        $indexHeaders = [
            [
                'text' => 'Vorname',
                'value' => 'firstName',
                'sortable' => true,
                'type' => 'text'
            ],
            [
                'text' => 'Nachname',
                'value' => 'lastName',
                'sortable' => true,
                'type' => 'text'
            ],
        ];

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $indexHeaders[] =  [
                'text' => 'E-Mail (Privat)',
                'value' => 'emailPrivate',
                'sortable' => true,
                'type' => 'email'
            ];
        }

        return $indexHeaders;
    }

    public function getFormFields(): array
    {
        $formFields = [
            [
                'text' => 'Vorname',
                'key' => 'firstName',
                'type' => 'text'
            ],
            [
                'text' => 'Nachname',
                'key' => 'lastName',
                'type' => 'text'
            ],
            [
                'text' => 'E-Mail Privat',
                'key' => 'emailPrivate',
                'type' => 'email'
            ],
        ];

        return $formFields;
    }
}