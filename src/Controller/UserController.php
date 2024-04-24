<?php

namespace App\Controller;

use App\Dto\Base64FileRequest;
use App\Repository\SystemSettingRepository;
use App\Service\User\ClientNavigation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/user')]
class UserController extends AbstractApiController
{
    public function __construct(
        private string $documentBaseDir,
    ) {
    }

    #[Route('/')]
    public function getUserInfo(
        ClientNavigation $clientNavigation,
        Request $request,
    ): Response {
        $me = $this->getUser();

        $userNavigation = $clientNavigation->getUserClientNavigation($me);

        // todo: make this optional by system_setting
        $userAvatar = '';
        if (file_exists($this->documentBaseDir . '/user/' . $this->getUser()->getId() . '_avatar.png')) {
            $userAvatar =  base64_encode(file_get_contents(
                $this->documentBaseDir . '/user/' . $this->getUser()->getId() . '_avatar.png'
            ));
        } else {
            if (file_exists($this->documentBaseDir . '/user/default_avatar_male.png')) {
                $userAvatar = base64_encode(file_get_contents(
                    $this->documentBaseDir . '/user/default_avatar_male.png'
                ));
            }
        }

        // todo: make this optional by system_setting
        $userAvatarBg = '';
        if (file_exists($this->documentBaseDir . '/user/' . $this->getUser()->getId() . '_background.jpg')) {
            $userAvatarBg =  base64_encode(file_get_contents(
                $this->documentBaseDir . '/user/' . $this->getUser()->getId() . '_background.jpg'
            ));
        } else {
            if (file_exists($this->documentBaseDir . '/user/default_bg.jpg')) {
                $userAvatarBg = base64_encode(file_get_contents(
                    $this->documentBaseDir . '/user/default_bg.jpg'
                ));
            }
        }

        $userApiData = [
            'user' => [
                'avatar' => $userAvatar,
                'background' => $userAvatarBg,
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

    #[Route(
        path: '/avatar',
        methods: ['POST']
    )]
    public function uploadAvatar(
        #[MapRequestPayload] Base64FileRequest $base64FileRequest,
    ): Response {
        $templateContent = base64_decode($base64FileRequest->getFile());
        $templateSavePath = $this->documentBaseDir . '/user/' . $this->getUser()->getId() . '_avatar.png';
        file_put_contents($templateSavePath, $templateContent);

        $userAvatar = '';
        if (file_exists($templateSavePath)) {
            $userAvatar = base64_encode(file_get_contents(
                $templateSavePath
            ));
        } else {
            if (file_exists($this->documentBaseDir . '/user/default_avatar_male.png')) {
                $userAvatar = base64_encode(file_get_contents(
                    $this->documentBaseDir . '/user/default_avatar_male.png'
                ));
            }
        }

        return $this->json([
            'avatar' => $userAvatar
        ]);
    }

    #[Route(
        path: '/background',
        methods: ['POST']
    )]
    public function uploadBackground(
        #[MapRequestPayload] Base64FileRequest $base64FileRequest,
    ): Response {
        $templateContent = base64_decode($base64FileRequest->getFile());
        $templateSavePath = $this->documentBaseDir . '/user/' . $this->getUser()->getId() . '_background.jpg';
        file_put_contents($templateSavePath, $templateContent);

        $userBackground = '';
        if (file_exists($templateSavePath)) {
            $userBackground = base64_encode(file_get_contents(
                $templateSavePath
            ));
        } else {
            if (file_exists($this->documentBaseDir . '/user/default_bg.jpg')) {
                $userBackground = base64_encode(file_get_contents(
                    $this->documentBaseDir . '/user/default_bg.jpg'
                ));
            }
        }

        return $this->json([
            'background' => $userBackground
        ]);
    }
}