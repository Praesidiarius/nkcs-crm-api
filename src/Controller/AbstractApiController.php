<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AbstractApiController extends AbstractController
{
    private array $licenseData = [];
    private array $licenseExtendedInfo = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    protected function checkLicense(): bool
    {
        $request = Request::createFromGlobals();

        if ($this->getParameter('license.server')) {
            $self = ($request->server->getBoolean('HTTPS') ? 'https://' : 'http://')
                . $request->server->get('HTTP_HOST');

            if (
                // if we are on the license server and test mode is not enabled, no need to check for a license
                $this->getParameter('license.server') === $self
                && !$this->getParameter('license.server_test')
            ) {
                return true;
            }

            // check license from cache if enabled
            if ($this->getParameter('license.cache')) {
                if (file_exists($this->getParameter('document.base_dir') . '/license.yaml')) {
                    $cachedLicense = Yaml::parseFile($this->getParameter('document.base_dir') . '/license.yaml', Yaml::PARSE_OBJECT);
                    $this->licenseData = $cachedLicense;

                    if (date('Y-m-d H:i:s', time()) < date('Y-m-d H:i:s', strtotime($cachedLicense['dateValid']))) {
                        return true;
                    }
                }
            }

            // get license from license server
            $response = $this->httpClient->request(
                'GET',
                $this->getParameter('license.server')
                    . '/license/check/'
                    . $this->getParameter('license.holder')
            );

            $licenseData = $response->toArray();

            $this->licenseData = $licenseData['license'];

            // update license cache if enabled
            if ($this->getParameter('license.cache')) {
                $yaml = Yaml::dump($licenseData['license']);

                file_put_contents($this->getParameter('document.base_dir') . '/license.yaml', $yaml);
            }

            return $licenseData['license']['isValid'];
        }

        return true;
    }

    protected function updateLicenseExtendedInfo(): void
    {
        $request = Request::createFromGlobals();

        if ($this->getParameter('license.server')) {
            // get license from license server
            $response = $this->httpClient->request(
                'GET',
                $this->getParameter('license.server')
                . '/license/info/'
                . $this->getParameter('license.holder')
            );

            $this->licenseExtendedInfo = $response->toArray();
        }
    }

    protected function isTrial(): bool
    {
        if (!$this->licenseExtendedInfo) {
            $this->updateLicenseExtendedInfo();
        }

        return ($this->licenseExtendedInfo['license']['isTrial'] ?? false)
            && count($this->licenseExtendedInfo['future']) === 0
            ;
    }

    protected function getLicenseValidUntilDate(): string
    {
        if (!$this->licenseExtendedInfo) {
            $this->updateLicenseExtendedInfo();
        }

        return $this->licenseExtendedInfo['license']['dateValid'];
    }

    protected function getLicenseProduct(): array
    {
        if (!$this->licenseExtendedInfo) {
            $this->updateLicenseExtendedInfo();
        }

        return $this->licenseExtendedInfo['license']['product'] ?? [];
    }

    protected function getFutureLicenses(): array
    {
        if (!$this->licenseExtendedInfo) {
            $this->updateLicenseExtendedInfo();
        }

        return $this->licenseExtendedInfo['future'] ?? [];
    }

    protected function getArchivedLicenses(): array
    {
        if (!$this->licenseExtendedInfo) {
            $this->updateLicenseExtendedInfo();
        }

        return $this->licenseExtendedInfo['archive'] ?? [];
    }
}