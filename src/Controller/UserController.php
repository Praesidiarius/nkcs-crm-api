<?php

namespace App\Controller;

use App\Repository\SystemSettingRepository;
use App\Service\User\ClientNavigation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/user')]
class UserController extends AbstractApiController
{
    #[Route('/')]
    public function getUserInfo(
        ClientNavigation $clientNavigation,
        Request $request,
    ): Response {
        $me = $this->getUser();

        $userNavigation = $clientNavigation->getUserClientNavigation($me);

        $userApiData = [
            'user' => [
                'name' => $me->getName(),
                'firstName' => $me->getFirstName(),
                'function' => $me->getFunction(),
                'email' => $me->getEmail(),
            ],
            'navigation' => $userNavigation
        ];

        if ($this->getParameter('license.server')) {
            // make sure we are on the license server instance
            $self = ($request->server->getBoolean('HTTPS') ? 'https://' : 'http://')
                . $request->server->get('HTTP_HOST');

            if ($this->getParameter('license.server') === $self) {
                $userApiData['license'] = [
                    'isTrial' => false,
                    'daysLeft' => 300,
                    'dateValid' => date('d.m.Y', strtotime('+300 days')),
                    'product' => null,
                    'future' => [],
                    'archive' => [],
                ];
            } else {
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
        }
        return $this->json($userApiData);
    }

    #[Route('/referral')]
    public function referralInfo(
        HttpClientInterface $httpClient,
    ): Response {
        // get license products from license server
        return $this->json(json_decode($httpClient->request(
            'GET',
            $this->getParameter('license.server')
            . '/license/referral/'
            . $this->getParameter('license.holder')
        )->getContent()));
    }
}