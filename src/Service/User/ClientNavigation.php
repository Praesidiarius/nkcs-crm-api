<?php

namespace App\Service\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ClientNavigation
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $activatedModules
    ) {

    }

    public function getUserClientNavigation(UserInterface $user): array
    {
        $activeModules = explode(',', $this->activatedModules);

        $menu = [];
        if (in_array('item', $activeModules)) {
            $menu[] = [
                'title' => $this->translator->trans('item.items'),
                'icon' => 'mdi-tag-text',
                'children' => [
                    [
                        'title' => $this->translator->trans('item.itemMultiple'),
                        'icon' => 'mdi-tag-text',
                        'to' => ['name' => 'ItemIndex']
                    ],
                    [
                        'title' => $this->translator->trans('item.add'),
                        'icon' => 'mdi-tag-plus',
                        'to' => ['name' => 'ItemAdd']
                    ],
                    [
                        'title' => $this->translator->trans('item.voucher.vouchers'),
                        'icon' => 'mdi-gift',
                        //'to' => ['name' => 'VoucherIndex']
                    ]
                ]
            ];
        }

        if (in_array('job', $activeModules)) {
            $menu[] = [
                'title' => $this->translator->trans('job.jobs'),
                'icon' => 'mdi-book',
                'children' => [
                    [
                        'title' => $this->translator->trans('job.jobsMultiple'),
                        'icon' => 'mdi-book',
                        'to' => ['name' => 'JobIndex']
                    ],
                    [
                        'title' => $this->translator->trans('job.add'),
                        'icon' => 'mdi-book-plus',
                        'to' => ['name' => 'JobAdd']
                    ]
                ]
            ];
        }

        if (in_array('contact', $activeModules)) {
            $menu[] = [
                'title' => $this->translator->trans('contact.contactAlt'),
                'icon' => 'mdi-account-group',
                'children' => [
                    [
                        'title' => $this->translator->trans('contact.contacts'),
                        'icon' => 'mdi-account-group',
                        'to' => ['name' => 'ContactIndex']
                    ],
                    [
                        'title' => $this->translator->trans('contact.add'),
                        'icon' => 'mdi-account-plus',
                        'to' => ['name' => 'ContactAdd']
                    ],
                    [
                        'title' => $this->translator->trans('contact.addCompany'),
                        'icon' => 'mdi-office-building-plus',
                        'to' => ['name' => 'ContactCompanyAdd']
                    ]
                ]
            ];
        }

        if (in_array('document', $activeModules)) {
            $menu[] = [
                'title' => $this->translator->trans('document.documents'),
                'icon' => 'mdi-file-word',
                'children' => [
                    [
                        'title' => $this->translator->trans('document.templates'),
                        'icon' => 'mdi-file-word',
                        'to' => ['name' => 'DocumentIndex']
                    ]
                ]
            ];
        }

        return $menu;
    }
}