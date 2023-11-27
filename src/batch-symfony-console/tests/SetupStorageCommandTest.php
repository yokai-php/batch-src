<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Yokai\Batch\Bridge\Symfony\Console\SetupStorageCommand;
use Yokai\Batch\Exception\JobExecutionNotFoundException;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\JobExecutionStorageInterface;
use Yokai\Batch\Storage\SetupableJobExecutionStorageInterface;

final class SetupStorageCommandTest extends TestCase
{
    public function testSetupRequired(): void
    {
        $this->execute(
            $storage = new class() implements
                JobExecutionStorageInterface,
                SetupableJobExecutionStorageInterface {
                public bool $wasSetup = false;

                public function setup(): void
                {
                    $this->wasSetup = true;
                }

                public function store(JobExecution $execution): void
                {
                }

                public function remove(JobExecution $execution): void
                {
                }

                public function retrieve(string $jobName, string $executionId): JobExecution
                {
                    throw new JobExecutionNotFoundException($jobName, $executionId);
                }
            },
            '[OK] The storage was set up successfully.',
        );
        self::assertTrue($storage->wasSetup);
    }

    public function testSetupNotRequired(): void
    {
        $this->execute(
            new class() implements JobExecutionStorageInterface {
                public function store(JobExecution $execution): void
                {
                }

                public function remove(JobExecution $execution): void
                {
                }

                public function retrieve(string $jobName, string $executionId): JobExecution
                {
                    throw new JobExecutionNotFoundException($jobName, $executionId);
                }
            },
            '! [NOTE] The storage does not support setup.',
        );
    }

    private function execute(JobExecutionStorageInterface $storage, string $expected): void
    {
        $tester = new CommandTester(new SetupStorageCommand($storage));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
        self::assertSame($expected, \trim($tester->getDisplay(true)));
    }
}
