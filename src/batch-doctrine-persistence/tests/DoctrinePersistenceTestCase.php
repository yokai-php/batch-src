<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\Persistence;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Dummy\SimpleManagerRegistry;

abstract class DoctrinePersistenceTestCase extends TestCase
{
    protected EntityManagerInterface $authManager;
    protected EntityManagerInterface $shopManager;
    protected ManagerRegistry $doctrine;

    protected function setUp(): void
    {
        // It is important to have both attribute & annotation configurations because
        // otherwise Doctrine do not seem to be able to find which manager is responsible
        // to manage an entity or another.
        $authConfig = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Entity/Auth'], true);
        $shopConfig = ORMSetup::createAnnotationMetadataConfiguration([__DIR__ . '/Entity/Shop'], true);

        $this->setUpConfigs($authConfig, $shopConfig);

        $connection = DriverManager::getConnection(['url' => \getenv('DATABASE_URL')]);
        $this->authManager = new EntityManager($connection, $authConfig);
        $this->shopManager = new EntityManager($connection, $shopConfig);

        $this->doctrine = new SimpleManagerRegistry(['auth' => $this->authManager, 'shop' => $this->shopManager]);

        /** @var EntityManager $manager */
        foreach ($this->doctrine->getManagers() as $manager) {
            (new SchemaTool($manager))
                ->createSchema($manager->getMetadataFactory()->getAllMetadata());
        }

        $this->setUpFixtures();
    }

    protected function setUpConfigs(Configuration $authConfig, Configuration $shopConfig): void
    {
    }

    protected function setUpFixtures(): void
    {
    }
}
