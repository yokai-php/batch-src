<?php

declare(strict_types=1);

namespace Yokai\Batch\Job\Item\Writer;

use Yokai\Batch\Job\Item\ItemWriterInterface;

final class CallbackWriter implements ItemWriterInterface
{
    public function __construct(
        private \Closure $callback,
    ) {
    }

    public function write(iterable $items): void
    {
        ($this->callback)($items);
    }
}
