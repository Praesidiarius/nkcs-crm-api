<?php

namespace App\Tests\Controller;

use App\Repository\ItemRepository;
use Faker\Factory;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversClass(\App\Controller\Item\ItemController::class)]
class ItemControllerTest extends WebTestCase
{
    use MatchesSnapshots;

    private static array $itemTestIds = [];
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

        self::$client->request('GET', '/api/item/de/add');

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
            'unit_id' => 1,
            'type_id' => 1,
            'name' => $faker->name,
            'price' => $faker->randomFloat(2, 5, 30),
        ];

        self::$client->request('POST', '/api/item/de/add', [], [],[],json_encode($formData));

        $this->assertResponseIsSuccessful();
        $this->assertSame(200, self::$client->getResponse()->getStatusCode());
        $responseData = json_decode(self::$client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('item', $responseData);
        $this->assertArrayHasKey('form', $responseData);
        $this->assertArrayHasKey('sections', $responseData);

        self::$itemTestIds[] = $responseData['item']['id'];
    }

    public function testIndex(): void
    {
        $this->logInAuthorizedUser('ROLE_ADMIN');

        self::$client->request('GET', '/api/item/de/list');

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
        $itemRepo = static::getContainer()->get(ItemRepository::class);
        foreach (self::$itemTestIds as $testId) {
            $itemRepo->removeById($testId);
        }
    }
}
