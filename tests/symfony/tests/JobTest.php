<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\Tests;

use Doctrine\DBAL\Platforms\Exception\NotSupported;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

final class JobTest extends KernelTestCase
{
    #[DataProvider('configs')]
    public function testUsingCli(string $job, callable $assert): void
    {
        $kernel = self::createKernel();
        $container = self::getContainer();
        self::setupDatabase();

        $application = new Application($kernel);

        $config = ['_id' => $id = \uniqid()];

        $command = $application->find('yokai:batch:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['job' => $job, 'configuration' => \json_encode($config)]);

        /** @var JobExecutionStorageInterface $storage */
        $storage = $container->get(JobExecutionStorageInterface::class);
        $assert($storage->retrieve($job, $id), $container);
    }

    #[DataProvider('configs')]
    public function testUsingLauncher(string $job, callable $assert): void
    {
        $container = self::getContainer();
        self::setupDatabase();

        /** @var JobLauncherInterface $launcher */
        $launcher = $container->get(JobLauncherInterface::class);

        $execution = $launcher->launch($job, $config ?? []);

        $assert($execution, $container);
    }

    public static function configs(): Generator
    {
        yield from CountryJobSet::sets();
        yield from StarWarsJobSet::sets();
        yield from RickAndMortyJobSet::sets();
    }

    private static function setupDatabase(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.default_entity_manager');
        $connection = $entityManager->getConnection();

        $database = $connection->getParams()['path'];
        if (\file_exists($database)) {
            \unlink($database);
        }

        try {
            $schema = $connection->createSchemaManager();
            $schema->createDatabase($database);
        } catch (NotSupported) {
            // when using sqlite, creating database is implicit
        }

        (new SchemaTool($entityManager))
            ->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
    }
}
