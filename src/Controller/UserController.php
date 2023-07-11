<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user')]
class UserController extends AbstractApiController
{
    #[Route('/')]
    public function getUserInfo(): Response {
        $me = $this->getUser();

        $userApiData = [
            'user' => [
                'name' => $me->getName(),
                'function' => $me->getFunction(),
                'email' => $me->getEmail(),
            ],
        ];

        if ($this->getParameter('license.server')) {
            $daysLeft = 0;
            if ($this->getLicenseValidUntilDate()) {
                if (strtotime($this->getLicenseValidUntilDate()) > time()) {
                    $daysLeft = round(((strtotime($this->getLicenseValidUntilDate())  - time()) / 3600) / 24);
                }
            }
            $userApiData['license'] = [
                'isTrial' => $this->isTrial(),
                'daysLeft' => $daysLeft,
                'dateValid' => $this->getLicenseValidUntilDate() ? date('d.m.Y', strtotime($this->getLicenseValidUntilDate())) : '',
                'product' => $this->getLicenseProduct(),
                'future' => $this->getFutureLicenses(),
                'archive' => $this->getArchivedLicenses(),
            ];
        }
        return $this->json($userApiData);
    }
}