<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Console;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\Bridge\Symfony\Console\CommandRunner;
use Yokai\Batch\Bridge\Symfony\Console\RunCommandJobLauncher;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\Factory\UniqidJobExecutionIdGenerator;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

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

        /** @var JobExecutionStorageInterface|ObjectProphecy $storage */
        $storage = $this->prophesize(JobExecutionStorageInterface::class);

        $jobExecutionAssertions = Argument::that(
            function ($jobExecution): bool {
                return $jobExecution instanceof JobExecution
                    && $jobExecution->getJobName() === 'testing'
                    && $jobExecution->getId() === '123456789'
                    && $jobExecution->getStatus()->is(BatchStatus::PENDING)
                    && $jobExecution->getParameters()->get('foo') === ['bar'];
            }
        );
        $storage->store($jobExecutionAssertions)->shouldBeCalledTimes(1);

        $launcher = new RunCommandJobLauncher(
            new JobExecutionFactory(new UniqidJobExecutionIdGenerator()),
            $commandRunner->reveal(),
            $storage->reveal(),
            'test.log'
        );
        $launcher->launch('testing', $config);
    }
}
