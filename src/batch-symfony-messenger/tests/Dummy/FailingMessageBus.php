<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Messenger\Dummy;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class FailingMessageBus implements MessageBusInterface
{
    public function __construct(
        private ExceptionInterface $exception,
    ) {
    }

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        throw $this->exception;
    }
}
