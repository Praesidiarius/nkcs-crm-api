<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(UserRepository::class)]
class UserRepositoryTest extends KernelTestCase
{
    private static array $userTestIds = [];
    private readonly UserRepository $repository;
    private readonly EntityManagerInterface $em;

    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->repository = $container->get(UserRepository::class);
        $this->em = $container->get(EntityManagerInterface::class);
    }

    public function testFindAll(): void
    {
        $allUsers = $this->repository->findAll();
        $this->assertIsArray($allUsers);
        $this->assertCount(1, $allUsers);
    }

    public function testSave(): void
    {
        $user = new User();
        $user->setUsername('unit_test_1');
        $user->setPassword('noPassword');
        $user->setFirstName('First');

        $this->assertNull($user->getId());

        $userUpdatedFromDB = $this->repository->save($user, true);

        $this->assertNotNull($userUpdatedFromDB->getId());

        self::$userTestIds['simple'] = $userUpdatedFromDB->getId();
    }

    public function testUpgradePassword(): void
    {
        $user = $this->repository->find(self::$userTestIds['simple']);

        $this->repository->upgradePassword($user, 'randomHash');

        $this->em->refresh($user);

        $this->assertEquals('randomHash', $user->getPassword());
    }

    public function testGetUserSettings(): void
    {
        $user = $this->repository->find(self::$userTestIds['simple']);

        $this->assertCount(0, $user->getUserSettings());
    }

    public function testRemove(): void
    {
        $user = $this->repository->find(self::$userTestIds['simple']);

        $this->repository->remove($user, true);

        $this->assertNull($this->repository->find(self::$userTestIds['simple']));
    }
}