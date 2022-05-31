<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Messenger\Writer;

use Symfony\Component\Messenger\MessageBusInterface;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Item\ItemWriterInterface;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;

/**
 * This {@see ItemWriterInterface} will consider each written item to be a message.
 * Every item will be sent individually to a {@see MessageBusInterface}.
 */
final class DispatchEachItemAsMessageWriter implements ItemWriterInterface, JobExecutionAwareInterface
{
    use JobExecutionAwareTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function write(iterable $items): void
    {
        foreach ($items as $item) {
            if (!\is_object($item)) {
                throw UnexpectedValueException::type('object', $item);
            }
            $this->messageBus->dispatch($item);
        }
    }
}
