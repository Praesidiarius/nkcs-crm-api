<?php

namespace App\Tests\Repository;

use App\Enum\JobVatMode;
use App\Model\JobDto;
use App\Repository\JobRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(JobRepository::class)]
class JobRepositoryTest extends KernelTestCase
{
    private static array $jobTestIds = [];
    private readonly JobRepository $repository;
    private readonly JobDto $dynamicDto;

    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->repository = $container->get(JobRepository::class);
        $this->dynamicDto = $container->get(JobDto::class);
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
        $job = $this->repository->getDynamicDto();
        $job->setTextField('title', 'Unit Test 1');
        $job->setPriceField('type_id', 1);
        $job->setIntField('vat_mode', JobVatMode::VAT_NONE->value);
        $job->setCreatedBy(0);
        $job->setCreatedDate();

        $job = $this->repository->save($job);

        // check if we now have to id from database
        $this->assertNotNull($job->getId());

        // save id for later tests
        self::$jobTestIds['simple'] = $job->getId();
    }

    public function testFindByIdExisting(): void
    {
        $testJobId = self::$jobTestIds['simple'];
        $jobFromDB = $this->repository->findById($testJobId);

        $this->assertEquals(JobDto::class, get_class($jobFromDB));
        $this->assertEquals('Unit Test 1', $jobFromDB->getTextField('title'));
    }

    public function testFindByAttribute(): void
    {
        $jobFromDB = $this->repository->findByAttribute('title', 'Unit Test 1');

        $this->assertEquals(JobDto::class, get_class($jobFromDB));
        $this->assertEquals('Unit Test 1', $jobFromDB->getTextField('title'));
    }

    public function testMostRecent(): void
    {
        $testJobId = self::$jobTestIds['simple'];
        $jobFromDB = $this->repository->findById($testJobId);

        $this->assertEquals($jobFromDB, $this->repository->findMostRecent());
    }

    public function testFindBySearchAttributes(): void
    {
        $allContacts = $this->repository->findBySearchAttributes(1, 25);

        $this->assertIsArray($allContacts);
        $this->assertSameSize(self::$jobTestIds, $allContacts);
    }

    public function testRemove(): void
    {
        $testJobId = self::$jobTestIds['simple'];
        $this->repository->removeById($testJobId);

        $this->assertEquals(null, $this->repository->findById($testJobId));

        unset(self::$jobTestIds['simple']);
    }
}