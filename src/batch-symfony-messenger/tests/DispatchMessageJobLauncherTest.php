<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Messenger;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\Bridge\Symfony\Messenger\DispatchMessageJobLauncher;
use Yokai\Batch\Bridge\Symfony\Messenger\LaunchJobMessage;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\Factory\UniqidJobExecutionIdGenerator;
use Yokai\Batch\Test\Factory\SequenceJobExecutionIdGenerator;
use Yokai\Batch\Test\Storage\InMemoryJobExecutionStorage;

final class DispatchMessageJobLauncherTest extends TestCase
{
    use ProphecyTrait;

    public function testLaunch(): void
    {
        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageAssertions = Argument::that(
            static function ($message): bool {
                return $message instanceof LaunchJobMessage
                    && $message->getJobName() === 'testing'
                    && $message->getConfiguration() === ['_id' => '123456789', 'foo' => ['bar']];
            }
        );
        $messageBus->dispatch($messageAssertions)
            ->shouldBeCalled()
            ->willReturn(new Envelope(new LaunchJobMessage('unused')));

        $jobLauncher = new DispatchMessageJobLauncher(
            new JobExecutionFactory(new UniqidJobExecutionIdGenerator()),
            $storage = new InMemoryJobExecutionStorage(),
            $messageBus->reveal()
        );

        $jobExecutionFromLauncher = $jobLauncher->launch('testing', ['_id' => '123456789', 'foo' => ['bar']]);

        [$jobExecutionFromStorage] = $storage->getExecutions();
        self::assertSame($jobExecutionFromLauncher, $jobExecutionFromStorage);

        self::assertSame('testing', $jobExecutionFromStorage->getJobName());
        self::assertSame('123456789', $jobExecutionFromStorage->getId());
        self::assertSame(BatchStatus::PENDING, $jobExecutionFromStorage->getStatus()->getValue());
        self::assertSame(['bar'], $jobExecutionFromStorage->getParameters()->get('foo'));
    }

    public function testLaunchWithNoId(): void
    {
        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageAssertions = Argument::that(
            static function ($message): bool {
                return $message instanceof LaunchJobMessage
                    && $message->getJobName() === 'testing'
                    && $message->getConfiguration() === ['_id' => '123456789'];
            }
        );
        $messageBus->dispatch($messageAssertions)
            ->shouldBeCalled()
            ->willReturn(new Envelope(new LaunchJobMessage('unused')));

        $jobLauncher = new DispatchMessageJobLauncher(
            new JobExecutionFactory(new SequenceJobExecutionIdGenerator(['123456789'])),
            $storage = new InMemoryJobExecutionStorage(),
            $messageBus->reveal()
        );

        $jobExecutionFromLauncher = $jobLauncher->launch('testing');

        [$jobExecutionFromStorage] = $storage->getExecutions();
        self::assertSame($jobExecutionFromLauncher, $jobExecutionFromStorage);

        self::assertSame('testing', $jobExecutionFromStorage->getJobName());
        self::assertSame('123456789', $jobExecutionFromStorage->getId());
        self::assertSame(BatchStatus::PENDING, $jobExecutionFromStorage->getStatus()->getValue());
    }
}
