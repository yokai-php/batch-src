<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Messenger;

use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\Symfony\Messenger\LaunchJobMessage;
use Yokai\Batch\Bridge\Symfony\Messenger\LaunchJobMessageHandler;
use Yokai\Batch\Test\Factory\SequenceJobExecutionIdGenerator;
use Yokai\Batch\Test\Launcher\BufferingJobLauncher;

final class LaunchJobMessageHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        $launcher = new BufferingJobLauncher(new SequenceJobExecutionIdGenerator(['123456']));

        $handler = new LaunchJobMessageHandler($launcher);
        $handler->__invoke(new LaunchJobMessage('foo', ['bar' => 'BAR']));

        self::assertCount(1, $launcher->getExecutions());
        self::assertSame('foo', $launcher->getExecutions()[0]->getJobName());
        self::assertSame('123456', $launcher->getExecutions()[0]->getId());
        self::assertSame(
            ['bar' => 'BAR', '_id' => '123456'],
            \iterator_to_array($launcher->getExecutions()[0]->getParameters()->getIterator())
        );
    }
}
