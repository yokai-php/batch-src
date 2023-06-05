<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Messenger\Dummy;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class BufferingMessageBus implements MessageBusInterface
{
    /**
     * @var Envelope[]
     */
    private array $envelopes = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->envelopes[] = $envelope = new Envelope($message, $stamps);

        return $envelope;
    }

    /**
     * @return object[]
     */
    public function getMessages(): array
    {
        return \array_map(fn(Envelope $envelope) => $envelope->getMessage(), $this->envelopes);
    }

    /**
     * @return Envelope[]
     */
    public function getEnvelopes(): array
    {
        return $this->envelopes;
    }
}
