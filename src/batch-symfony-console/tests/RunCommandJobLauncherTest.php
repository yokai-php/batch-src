<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Console;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\Bridge\Symfony\Console\CommandRunner;
use Yokai\Batch\Bridge\Symfony\Console\RunCommandJobLauncher;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\Factory\JobExecutionLoggerFactory\InMemoryJobExecutionLoggerFactory;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\NullJobExecutionParametersBuilder;
use Yokai\Batch\Factory\UniqidJobExecutionIdGenerator;
use Yokai\Batch\Test\Storage\InMemoryJobExecutionStorage;

final class RunCommandJobLauncherTest extends TestCase
{
    public function testLaunch(): void
    {
        $config = ['_id' => '123456789', 'foo' => ['bar']];
        $arguments = ['job' => 'testing', 'configuration' => '{"_id":"123456789","foo":["bar"]}'];

        /** @var MockObject&CommandRunner $commandRunner */
        $commandRunner = $this->createMock(CommandRunner::class);
        $commandRunner->expects($this->once())
            ->method('runAsync')
            ->with('yokai:batch:run', 'test.log', $arguments);

        $launcher = new RunCommandJobLauncher(
            new JobExecutionFactory(
                new UniqidJobExecutionIdGenerator(),
                new NullJobExecutionParametersBuilder(),
                new InMemoryJobExecutionLoggerFactory(),
            ),
            $commandRunner,
            $storage = new InMemoryJobExecutionStorage(),
            'test.log',
        );

        $jobExecutionFromLauncher = $launcher->launch('testing', $config);

        [$jobExecutionFromStorage] = $storage->getExecutions();
        self::assertSame($jobExecutionFromLauncher, $jobExecutionFromStorage);

        self::assertSame('testing', $jobExecutionFromStorage->getJobName());
        self::assertSame('123456789', $jobExecutionFromStorage->getId());
        self::assertSame(BatchStatus::PENDING, $jobExecutionFromStorage->getStatus()->getValue());
        self::assertSame(['bar'], $jobExecutionFromStorage->getParameters()->get('foo'));
    }
}
