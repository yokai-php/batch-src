<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\Tests;

use Generator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

final class JobTest extends KernelTestCase
{
    protected static $booted = false;

    protected static function getContainer(): ContainerInterface
    {
        if (\method_exists(KernelTestCase::class, __METHOD__)) {
            return parent::getContainer();
        }

        if (!static::$booted) {
            static::bootKernel();
        }

        return static::$container;
    }

    /**
     * @dataProvider configs
     */
    public function testUsingCli(string $job, callable $assert, callable $setup = null, array $config = []): void
    {
        $kernel = self::createKernel();
        $container = self::getContainer();

        if ($setup !== null) {
            $setup($container);
        }

        $application = new Application($kernel);

        $config['_id'] = $id = \uniqid();

        $command = $application->find('yokai:batch:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['job' => $job, 'configuration' => \json_encode($config)]);

        /** @var JobExecutionStorageInterface $storage */
        $storage = $container->get(JobExecutionStorageInterface::class);
        $assert($storage->retrieve($job, $id), $container);
    }

    /**
     * @dataProvider configs
     */
    public function testUsingLauncher(string $job, callable $assert, callable $setup = null, array $config = []): void
    {
        $container = self::getContainer();

        if ($setup !== null) {
            $setup($container);
        }

        /** @var JobLauncherInterface $launcher */
        $launcher = $container->get('yokai_batch.job_launcher.simple');

        $execution = $launcher->launch($job, $config ?? []);

        $assert($execution, $container);
    }

    public function configs(): Generator
    {
        yield from CountryJobSet::sets();
        yield from StarWarsJobSet::sets();
    }
}
