<?php

namespace App\Tests\Repository;

use App\Model\DynamicDto;
use App\Repository\ContactAddressRepository;
use App\Repository\ContactRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(ContactRepository::class)]
class ContactRepositoryTest extends KernelTestCase
{
    private static array $contactTestIds = [];
    private readonly ContactRepository $repository;
    private readonly ContactAddressRepository $addressRepository;
    private readonly DynamicDto $dynamicDto;

    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->repository = $container->get(ContactRepository::class);
        $this->addressRepository = $container->get(ContactAddressRepository::class);
        $this->dynamicDto = $container->get(DynamicDto::class);
    }

    public function testFindAll(): void
    {
        $this->assertIsArray($this->repository->findAll());
    }

    public function testFindByIdNotExisting(): void
    {
        $this->assertEquals(null, $this->repository->findById(1));
    }

    public function testGetDynamicDto(): void
    {
        $this->assertEquals($this->dynamicDto, $this->repository->getDynamicDto());
    }

    public function testSaveWithoutAddress(): void
    {
        $contact = $this->repository->getDynamicDto();
        $contact->setBoolField('is_company', false);
        $contact->setTextField('email_private', 'unit@test.com');
        $contact->setTextField('first_name', 'UnitTest1');
        $contact->setTextField('signup_token', 'UnitTestToken1');
        $contact->setCreatedBy(0);
        $contact->setCreatedDate();

        $contact = $this->repository->save($contact);

        // check if we now have to id from database
        $this->assertNotNull($contact->getId());

        // save id for later tests
        self::$contactTestIds['without-address'] = $contact->getId();
    }

    public function testSaveWithAddress(): void
    {
        $contact = $this->repository->getDynamicDto();
        $contact->setBoolField('is_company', false);
        $contact->setTextField('email_private', 'unit@test.com');
        $contact->setTextField('first_name', 'UnitTest1');
        $contact->setTextField('signup_token', 'UnitTestToken1');
        $contact->setCreatedBy(0);
        $contact->setCreatedDate();

        $contact = $this->repository->save($contact);

        // check if we now have to id from database
        $this->assertNotNull($contact->getId());

        $address = $this->addressRepository->getDynamicDto();
        $address->setIntField('contact_id', $contact->getId());
        $address->setTextField('street', 'Unit Test 1');
        $address->setTextField('zip', '9999');
        $address->setTextField('city', 'Unit Test');

        $this->addressRepository->save($address);

        // save id for later tests
        self::$contactTestIds['with-address'] = $contact->getId();
    }

    public function testFindByIdExisting(): void
    {
        $testContactId = self::$contactTestIds['without-address'];
        $contactFromDB = $this->repository->findById($testContactId);

        $this->assertEquals(DynamicDto::class, get_class($contactFromDB));
        $this->assertFalse($contactFromDB->getBoolField('is_company'));
        $this->assertEquals('UnitTest1', $contactFromDB->getTextField('first_name'));
    }

    public function testFindByEmail(): void
    {
        $contactFromDB = $this->repository->findByEmail('unit@test.com');

        $this->assertEquals(DynamicDto::class, get_class($contactFromDB));
        $this->assertFalse($contactFromDB->getBoolField('is_company'));
        $this->assertEquals('UnitTest1', $contactFromDB->getTextField('first_name'));
    }

    public function testIsEmailAddressAlreadyInUse(): void
    {
        # does exist
        $this->assertTrue($this->repository->isEmailAddressAlreadyInUse('unit@test.com'));

        # does not exist
        $this->assertFalse($this->repository->isEmailAddressAlreadyInUse('unit22@test.com'));
    }

    public function testFindByAttribute(): void
    {
        $contactFromDB = $this->repository->findByAttribute('email_private', 'unit@test.com');

        $this->assertEquals(DynamicDto::class, get_class($contactFromDB));
        $this->assertFalse($contactFromDB->getBoolField('is_company'));
        $this->assertEquals('UnitTest1', $contactFromDB->getTextField('first_name'));
    }

    public function testFindBySignupToken(): void
    {
        $contactFromDB = $this->repository->findBySignupToken('UnitTestToken1');

        $this->assertEquals(DynamicDto::class, get_class($contactFromDB));
        $this->assertFalse($contactFromDB->getBoolField('is_company'));
        $this->assertEquals('UnitTest1', $contactFromDB->getTextField('first_name'));
    }

    public function testMostRecent(): void
    {
        $testContactId = self::$contactTestIds['with-address'];
        $contactFromDB = $this->repository->findById($testContactId);

        $this->assertEquals($contactFromDB, $this->repository->findMostRecent());
    }

    public function testFindBySearchAttributes(): void
    {
        $allContacts = $this->repository->findBySearchAttributes(1, 25);

        $this->assertIsArray($allContacts);
        $this->assertSameSize(self::$contactTestIds, $allContacts);
    }

    public function testRemoveWithoutAddress(): void
    {
        $testContactId = self::$contactTestIds['without-address'];
        $this->repository->removeById($testContactId);

        $this->assertEquals(null, $this->repository->findById($testContactId));

        unset(self::$contactTestIds['without-address']);
    }

    public function testRemoveWithAddress(): void
    {
        $testContactId = self::$contactTestIds['with-address'];
        $this->repository->removeById($testContactId);

        $this->assertEquals(null, $this->repository->findById($testContactId));

        unset(self::$contactTestIds['with-address']);
    }
}