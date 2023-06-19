<?php

namespace App\Tests\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactControllerTest extends WebTestCase
{
    private readonly KernelBrowser $client;

    public function logInAuthorizedUser(string $role): void
    {
        /** @var JWTEncoderInterface $jwtEncoder */
        $jwtEncoder = $this->client->getContainer()->get('lexik_jwt_authentication.encoder');

        $token = $jwtEncoder->encode([
            'username' => 'dev',
            'permissions' => [$role],
            'user_id' => 1,
            'first_name' => 'Test',
            'last_name' => 'Api',
            'email' => 'test.api@nkcs.com',
            'locale' => 'de'
        ]);

        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
    }


    public function setUp(): void
    {
        $this->client = static::createClient([], [
            'HTTP_HOST' => 'api-dev.vivid-crm.io'
        ]);

        parent::setUp();
    }

    public function testIndex(): void
    {
        $this->logInAuthorizedUser('ROLE_ADMIN');

        $this->client->request('GET', '/api/contact/de/list');

        $this->assertResponseIsSuccessful();

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('headers', $responseData);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total_items', $responseData);
        $this->assertArrayHasKey('pagination', $responseData);
    }

    public function testAddForm(): void
    {
        $this->logInAuthorizedUser('ROLE_ADMIN');

        $this->client->request('GET', '/api/contact/de/add');

        $this->assertResponseIsSuccessful();

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('form', $responseData);
        $this->assertArrayHasKey('sections', $responseData);
    }

    public function testAddCompanyForm(): void
    {
        $this->logInAuthorizedUser('ROLE_ADMIN');

        $this->client->request('GET', '/api/contact/de/add-company');

        $this->assertResponseIsSuccessful();

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('form', $responseData);
        $this->assertArrayHasKey('sections', $responseData);

    }

    public function testEditForm(): void
    {
        $this->logInAuthorizedUser('ROLE_ADMIN');

        $this->client->request('GET', '/api/contact/de/edit/1');

        $this->assertResponseIsSuccessful();

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('form', $responseData);
        $this->assertArrayHasKey('sections', $responseData);
    }
}
