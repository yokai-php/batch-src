<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\Persistence;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
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
        $authConfig = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Entity/Auth'], true);
        $shopConfig = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Entity/Shop'], true);
        if (PHP_VERSION_ID >= 80400) {
            $authConfig->enableNativeLazyObjects(true);
            $shopConfig->enableNativeLazyObjects(true);
        } else {
            $authConfig->setProxyDir(\sys_get_temp_dir());
            $authConfig->setProxyNamespace('DoctrineProxies');
            $shopConfig->setProxyDir(\sys_get_temp_dir());
            $shopConfig->setProxyNamespace('DoctrineProxies');
        }

        $this->setUpConfigs($authConfig, $shopConfig);

        $connection = DriverManager::getConnection((new DsnParser())->parse(\getenv('DATABASE_URL')));
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
