<?php

namespace App\Tests\Repository;

use App\Model\DynamicDto;
use App\Repository\ItemRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(ItemRepository::class)]
class ItemRepositoryTest extends KernelTestCase
{
    private static array $itemTestIds = [];
    private readonly ItemRepository $repository;
    private readonly DynamicDto $dynamicDto;

    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->repository = $container->get(ItemRepository::class);
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

    public function testSave(): void
    {
        $item = $this->repository->getDynamicDto();
        $item->setTextField('name', 'Unit Test 1');
        $item->setIntField('unit_id', 1);
        $item->setPriceField('price', 30.50);
        $item->setCreatedBy(0);
        $item->setCreatedDate();

        $item = $this->repository->save($item);

        // check if we now have to id from database
        $this->assertNotNull($item->getId());

        // save id for later tests
        self::$itemTestIds['simple'] = $item->getId();
    }

    public function testFindByIdExisting(): void
    {
        $testItemId = self::$itemTestIds['simple'];
        $itemFromDB = $this->repository->findById($testItemId);

        $this->assertEquals(DynamicDto::class, get_class($itemFromDB));
        $this->assertEquals(30.50, $itemFromDB->getPriceField('price'));
        $this->assertEquals(1, $itemFromDB->getIntField('unit_id'));
        $this->assertEquals('Unit Test 1', $itemFromDB->getTextField('name'));
    }

    public function testFindByAttribute(): void
    {
        $itemFromDB = $this->repository->findByAttribute('price', 30.50);

        $this->assertEquals(DynamicDto::class, get_class($itemFromDB));
        $this->assertEquals('Unit Test 1', $itemFromDB->getTextField('name'));
    }

    public function testMostRecent(): void
    {
        $testItemId = self::$itemTestIds['simple'];
        $itemFromDB = $this->repository->findById($testItemId);

        $this->assertEquals($itemFromDB, $this->repository->findMostRecent());
    }

    public function testFindBySearchAttributes(): void
    {
        $allContacts = $this->repository->findBySearchAttributes(1, 25);

        $this->assertIsArray($allContacts);
        $this->assertSameSize(self::$itemTestIds, $allContacts);
    }

    public function testRemove(): void
    {
        $testItemId = self::$itemTestIds['simple'];
        $this->repository->removeById($testItemId);

        $this->assertEquals(null, $this->repository->findById($testItemId));

        unset(self::$itemTestIds['simple']);
    }
}