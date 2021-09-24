<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Console;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\Bridge\Symfony\Console\CommandRunner;
use Yokai\Batch\Bridge\Symfony\Console\RunCommandJobLauncher;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\Factory\UniqidJobExecutionIdGenerator;
use Yokai\Batch\Test\Storage\InMemoryJobExecutionStorage;

class RunCommandJobLauncherTest extends TestCase
{
    use ProphecyTrait;

    public function testLaunch(): void
    {
        $config = ['_id' => '123456789', 'foo' => ['bar']];
        $arguments = ['job' => 'testing', 'configuration' => '{"_id":"123456789","foo":["bar"]}'];

        /** @var CommandRunner|ObjectProphecy $commandRunner */
        $commandRunner = $this->prophesize(CommandRunner::class);
        $commandRunner->runAsync('yokai:batch:run', 'test.log', $arguments)
            ->shouldBeCalledTimes(1);

        $launcher = new RunCommandJobLauncher(
            new JobExecutionFactory(new UniqidJobExecutionIdGenerator()),
            $commandRunner->reveal(),
            $storage = new InMemoryJobExecutionStorage(),
            'test.log'
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
