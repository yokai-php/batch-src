<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Generator;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\Doctrine\ORM\EntityReader;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Tests\Bridge\Doctrine\ORM\Dummy\SingleManagerRegistry;
use Yokai\Batch\Tests\Bridge\Doctrine\ORM\Entity\Unknown;
use Yokai\Batch\Tests\Bridge\Doctrine\ORM\Entity\User;

class EntityReaderTest extends TestCase
{
    private EntityManager $manager;
    private ManagerRegistry $doctrine;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Entity'], true);
        $this->manager = EntityManager::create(['url' => \getenv('DATABASE_URL')], $config);
        $this->doctrine = new SingleManagerRegistry($this->manager);

        (new SchemaTool($this->manager))
            ->createSchema($this->manager->getMetadataFactory()->getAllMetadata());
    }

    public function testRead(): void
    {
        $this->manager->persist($forest = new User('Forest'));
        $this->manager->persist($jenny = new User('Jenny'));
        $this->manager->persist($bubba = new User('Bubba'));
        $this->manager->flush();

        $reader = new EntityReader($this->doctrine, User::class);
        $entities = $reader->read();

        self::assertInstanceOf(Generator::class, $entities);
        self::assertSame([$forest, $jenny, $bubba], \iterator_to_array($entities));
    }

    public function testReadExceptionWithUnknownEntityClass(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $reader = new EntityReader($this->doctrine, Unknown::class);
        $entities = $reader->read();

        self::assertInstanceOf(Generator::class, $entities);
        \iterator_to_array($entities);
    }
}
