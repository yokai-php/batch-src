<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\Persistence;

use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectWriter;
use Yokai\Batch\Exception\InvalidArgumentException;
use Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Entity\Auth\Group;
use Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Entity\Auth\User;
use Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Entity\Shop\Product;
use Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Entity\Unknown;

class ObjectWriterTest extends DoctrinePersistenceTestCase
{
    public function testWriteSingleManager(): void
    {
        $userCreated = new User('initialized');
        $groupCreated = new Group('initialized');

        $userUpdated = new User('should be changed');
        $this->authManager->persist($userUpdated);
        $this->authManager->flush();
        $userUpdated->name = 'updated';

        $productNotUpdated = new Product('should not be changed');
        $this->shopManager->persist($productNotUpdated);
        $this->shopManager->flush();
        $productNotUpdated->name = 'updated';

        $writer = new ObjectWriter($this->doctrine);
        $writer->write([$userUpdated, $userCreated, $groupCreated]);

        // After write operations, managers are cleared, so objects are fetched from database
        $this->authManager->clear();
        $this->shopManager->clear();

        $userUpdated = $this->authManager->find(User::class, $userUpdated->id);
        self::assertNotNull($userUpdated);
        self::assertSame('updated', $userUpdated->name);

        self::assertTrue(isset($userCreated->id));
        $userCreated = $this->authManager->find(User::class, $userCreated->id);
        self::assertNotNull($userCreated);
        self::assertSame('initialized', $userCreated->name);

        self::assertTrue(isset($groupCreated->id));
        $groupCreated = $this->authManager->find(Group::class, $groupCreated->id);
        self::assertNotNull($groupCreated);
        self::assertSame('initialized', $groupCreated->name);

        self::assertTrue(isset($productNotUpdated->id));
        $productNotUpdated = $this->shopManager->find(Product::class, $productNotUpdated->id);
        self::assertNotNull($productNotUpdated);
        self::assertSame('should not be changed', $productNotUpdated->name);
    }

    public function testWriteMultipleManagers(): void
    {
        $userCreated = new User('initialized');
        $groupCreated = new Group('initialized');
        $productCreated = new Product('initialized');

        $userUpdated = new User('should be changed');
        $this->authManager->persist($userUpdated);
        $this->authManager->flush();
        $userUpdated->name = 'updated';

        $productUpdated = new Product('should be changed');
        $this->shopManager->persist($productUpdated);
        $this->shopManager->flush();
        $productUpdated->name = 'updated';

        $writer = new ObjectWriter($this->doctrine);
        $writer->write([$userUpdated, $productUpdated, $groupCreated]);
        $writer->write([$userCreated, $productCreated]);

        // After write operations, managers are cleared, so objects are fetched from database
        $this->authManager->clear();
        $this->shopManager->clear();

        $userUpdated = $this->authManager->find(User::class, $userUpdated->id);
        self::assertNotNull($userUpdated);
        self::assertSame('updated', $userUpdated->name);

        self::assertTrue(isset($userCreated->id));
        $userCreated = $this->authManager->find(User::class, $userCreated->id);
        self::assertNotNull($userCreated);
        self::assertSame('initialized', $userCreated->name);

        self::assertTrue(isset($groupCreated->id));
        $groupCreated = $this->authManager->find(Group::class, $groupCreated->id);
        self::assertNotNull($groupCreated);
        self::assertSame('initialized', $groupCreated->name);

        self::assertTrue(isset($productCreated->id));
        $productCreated = $this->shopManager->find(Product::class, $productCreated->id);
        self::assertNotNull($productCreated);
        self::assertSame('initialized', $productCreated->name);

        self::assertTrue(isset($productUpdated->id));
        $productUpdated = $this->shopManager->find(Product::class, $productUpdated->id);
        self::assertNotNull($productUpdated);
        self::assertSame('updated', $productUpdated->name);
    }

    public function testWriteThrowExceptionWithNonObject(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $writer = new ObjectWriter($this->doctrine);
        $writer->write(['string']);
    }

    public function testWriteThrowExceptionWithNonManagedObjects(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $writer = new ObjectWriter($this->doctrine);
        $writer->write([new Unknown()]);
    }
}
