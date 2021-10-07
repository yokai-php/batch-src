<?php

namespace Yokai\Batch\Sources\Tests\Symfony\Tests;

use Generator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

final class JobTest extends KernelTestCase
{
    /**
     * @dataProvider configs
     */
    public function testUsingCli(string $job, callable $assert, array $config = []): void
    {
        $kernel = self::createKernel();
        $application = new Application($kernel);

        $config['_id'] = $id = \uniqid();

        $command = $application->find('yokai:batch:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['job' => $job, 'configuration' => \json_encode($config)]);

        /** @var JobExecutionStorageInterface $storage */
        $storage = self::getContainer()->get(JobExecutionStorageInterface::class);
        $assert($storage->retrieve($job, $id));
    }

    /**
     * @dataProvider configs
     */
    public function testUsingLauncher(string $job, callable $assert, array $config = []): void
    {
        /** @var JobLauncherInterface $launcher */
        $launcher = self::getContainer()->get('yokai_batch.job_launcher.simple');

        $execution = $launcher->launch($job, $config ?? []);

        $assert($execution);
    }

    public function configs(): Generator
    {
        yield from CountryJobSet::sets();
    }
}
