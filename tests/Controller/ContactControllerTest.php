<?php

namespace App\Tests\Controller;

use App\Controller\Contact\ContactController;
use App\Repository\ContactRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Faker\Factory;

#[CoversClass(ContactController::class)]
class ContactControllerTest extends WebTestCase
{
    use MatchesSnapshots;

    private static array $contactTestIds = [];
    protected static ?KernelBrowser $client = null;

    public function logInAuthorizedUser(string $role): void
    {
        self::ensureKernelShutdown();

        self::$client = static::createClient([], []);
        /** @var JWTEncoderInterface $jwtEncoder */
        $jwtEncoder = self::$client->getContainer()->get('lexik_jwt_authentication.encoder');

        $token = $jwtEncoder->encode([
            'username' => 'dev',
            'permissions' => [$role],
            'user_id' => 1,
            'first_name' => 'Test',
            'last_name' => 'Api',
            'email' => 'test.api@nkcs.com',
            'locale' => 'de'
        ]);

        self::$client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
    }

    public function testInitAddForm(): void
    {
        $this->logInAuthorizedUser('ROLE_ADMIN');

        self::$client->request('GET', '/api/contact/de/add');

        $this->assertResponseIsSuccessful();

        $this->assertSame(200, self::$client->getResponse()->getStatusCode());
        $responseData = json_decode(self::$client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('form', $responseData);
        $this->assertArrayHasKey('sections', $responseData);
    }

    public function testInitAddCompanyForm(): void
    {
        $this->logInAuthorizedUser('ROLE_ADMIN');

        self::$client->request('GET', '/api/contact/de/add-company');

        $this->assertResponseIsSuccessful();

        $this->assertSame(200, self::$client->getResponse()->getStatusCode());
        $responseData = json_decode(self::$client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('form', $responseData);
        $this->assertArrayHasKey('sections', $responseData);
    }

    public function testSaveAddForm(): void
    {
        $this->logInAuthorizedUser('ROLE_ADMIN');

        $faker = Factory::create();

        $formData = [
            'salution_id' => 1,
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'email_private' => $faker->email,
            'phone' => $faker->phoneNumber,
            'street' => $faker->streetAddress,
            'zip' => $faker->numberBetween(1111,9999),
            'city' => $faker->city,
            'is_company' => 0,
        ];

        self::$client->request('POST', '/api/contact/de/add', [], [],[],json_encode($formData));

        $this->assertResponseIsSuccessful();
        $this->assertSame(200, self::$client->getResponse()->getStatusCode());
        $responseData = json_decode(self::$client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('item', $responseData);
        $this->assertArrayHasKey('form', $responseData);
        $this->assertArrayHasKey('sections', $responseData);

        self::$contactTestIds[] = $responseData['item']['id'];
    }

    public function testIndex(): void
    {
        $this->logInAuthorizedUser('ROLE_ADMIN');

        self::$client->request('GET', '/api/contact/de/list');

        $this->assertResponseIsSuccessful();

        $this->assertSame(200, self::$client->getResponse()->getStatusCode());
        $responseData = json_decode(self::$client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('headers', $responseData);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total_items', $responseData);
        $this->assertArrayHasKey('pagination', $responseData);

        $this->assertMatchesJsonSnapshot($responseData['headers']);
        $this->assertEquals(1, $responseData['total_items']);
        $this->assertEquals(1, $responseData['pagination']['page']);
        $this->assertEquals(1, $responseData['pagination']['page_count']);

    }

    public static function tearDownAfterClass(): void
    {
        $contactRepo = static::getContainer()->get(ContactRepository::class);
        foreach (self::$contactTestIds as $testId) {
            $contactRepo->removeById($testId);
        }
    }
}
