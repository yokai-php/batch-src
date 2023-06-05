<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Messenger;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Yokai\Batch\Bridge\Symfony\Messenger\LaunchJobMessage;
use Yokai\Batch\Bridge\Symfony\Messenger\LaunchJobMessageHandler;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\Job\JobExecutionAccessor;
use Yokai\Batch\Job\JobExecutor;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Registry\JobRegistry;
use Yokai\Batch\Test\Factory\SequenceJobExecutionIdGenerator;
use Yokai\Batch\Test\Storage\InMemoryJobExecutionStorage;

final class LaunchJobMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testInvoke(): void
    {
        $job = new class() implements JobInterface {
            public JobExecution $execution;

            public function execute(JobExecution $jobExecution): void
            {
                $this->execution = $jobExecution;
            }
        };

        $jobExecutionStorage = new InMemoryJobExecutionStorage();
        $handler = new LaunchJobMessageHandler(
            new JobExecutionAccessor(
                new JobExecutionFactory(new SequenceJobExecutionIdGenerator(['123456'])),
                $jobExecutionStorage,
            ),
            new JobExecutor(
                JobRegistry::fromJobArray(['foo' => $job]),
                $jobExecutionStorage,
                null
            )
        );
        $handler->__invoke(new LaunchJobMessage('foo', ['bar' => 'BAR']));

        self::assertSame('foo', $job->execution->getJobName());
        self::assertSame('123456', $job->execution->getId());
        self::assertSame(
            ['bar' => 'BAR', '_id' => '123456'],
            $job->execution->getParameters()->all()
        );
    }
}
