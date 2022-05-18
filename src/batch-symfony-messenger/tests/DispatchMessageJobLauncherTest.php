<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Messenger;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Exception\TransportException;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\Bridge\Symfony\Messenger\DispatchMessageJobLauncher;
use Yokai\Batch\Bridge\Symfony\Messenger\LaunchJobMessage;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\Factory\UniqidJobExecutionIdGenerator;
use Yokai\Batch\Test\Factory\SequenceJobExecutionIdGenerator;
use Yokai\Batch\Test\Storage\InMemoryJobExecutionStorage;
use Yokai\Batch\Tests\Bridge\Symfony\Messenger\Dummy\BufferingMessageBus;
use Yokai\Batch\Tests\Bridge\Symfony\Messenger\Dummy\FailingMessageBus;

final class DispatchMessageJobLauncherTest extends TestCase
{
    public function testLaunch(): void
    {
        $jobLauncher = new DispatchMessageJobLauncher(
            new JobExecutionFactory(new UniqidJobExecutionIdGenerator()),
            $storage = new InMemoryJobExecutionStorage(),
            $messageBus = new BufferingMessageBus()
        );

        $jobExecutionFromLauncher = $jobLauncher->launch('testing', ['_id' => '123456789', 'foo' => ['bar']]);

        [$jobExecutionFromStorage] = $storage->getExecutions();
        self::assertSame($jobExecutionFromLauncher, $jobExecutionFromStorage);

        self::assertSame('testing', $jobExecutionFromStorage->getJobName());
        self::assertSame('123456789', $jobExecutionFromStorage->getId());
        self::assertSame(BatchStatus::PENDING, $jobExecutionFromStorage->getStatus()->getValue());
        self::assertSame(['bar'], $jobExecutionFromStorage->getParameters()->get('foo'));
        self::assertJobWasTriggered($messageBus, 'testing', ['_id' => '123456789', 'foo' => ['bar']]);
    }

    public function testLaunchWithNoId(): void
    {
        $jobLauncher = new DispatchMessageJobLauncher(
            new JobExecutionFactory(new SequenceJobExecutionIdGenerator(['123456789'])),
            $storage = new InMemoryJobExecutionStorage(),
            $messageBus = new BufferingMessageBus()
        );

        $jobExecutionFromLauncher = $jobLauncher->launch('testing');

        [$jobExecutionFromStorage] = $storage->getExecutions();
        self::assertSame($jobExecutionFromLauncher, $jobExecutionFromStorage);

        self::assertSame('testing', $jobExecutionFromStorage->getJobName());
        self::assertSame('123456789', $jobExecutionFromStorage->getId());
        self::assertSame(BatchStatus::PENDING, $jobExecutionFromStorage->getStatus()->getValue());
        self::assertJobWasTriggered($messageBus, 'testing', ['_id' => '123456789']);
    }

    public function testLaunchAndMessengerFail(): void
    {
        $jobLauncher = new DispatchMessageJobLauncher(
            new JobExecutionFactory(new UniqidJobExecutionIdGenerator()),
            $storage = new InMemoryJobExecutionStorage(),
            new FailingMessageBus(new TransportException('This is a test'))
        );

        $jobExecutionFromLauncher = $jobLauncher->launch('testing');

        [$jobExecutionFromStorage] = $storage->getExecutions();
        self::assertSame($jobExecutionFromLauncher, $jobExecutionFromStorage);

        self::assertSame('testing', $jobExecutionFromStorage->getJobName());
        self::assertSame(BatchStatus::FAILED, $jobExecutionFromStorage->getStatus()->getValue());
        self::assertCount(1, $jobExecutionFromStorage->getFailures());
        $failure = $jobExecutionFromStorage->getFailures()[0];
        self::assertSame(TransportException::class, $failure->getClass());
        self::assertSame('This is a test', $failure->getMessage());
    }

    private static function assertJobWasTriggered(BufferingMessageBus $bus, string $jobName, array $config): void
    {
        $messages = $bus->getMessages();
        self::assertCount(1, $messages);
        $message = $messages[0];
        self::assertInstanceOf(LaunchJobMessage::class, $message);
        self::assertSame($jobName, $message->getJobName());
        self::assertSame($config, $message->getConfiguration());
    }
}
