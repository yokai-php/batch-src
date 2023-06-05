<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Messenger\Writer;

use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\Symfony\Messenger\Writer\DispatchEachItemAsMessageWriter;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Tests\Bridge\Symfony\Messenger\Dummy\BufferingMessageBus;
use Yokai\Batch\Tests\Bridge\Symfony\Messenger\Dummy\DummyMessage;

class DispatchEachItemAsMessageWriterTest extends TestCase
{
    public function test(): void
    {
        $writer = new DispatchEachItemAsMessageWriter($messageBus = new BufferingMessageBus());
        $writer->write([$message1 = new DummyMessage(), $message2 = new DummyMessage()]);
        self::assertSame([$message1, $message2], $messageBus->getMessages());
    }

    public function testInvalidItemType(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expecting argument to be object, but got int.');
        $writer = new DispatchEachItemAsMessageWriter(new BufferingMessageBus());
        $writer->write([1]);
    }
}
