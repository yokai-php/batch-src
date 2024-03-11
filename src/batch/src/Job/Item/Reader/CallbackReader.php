<?php

declare(strict_types=1);

namespace Yokai\Batch\Job\Item\Reader;

use Yokai\Batch\Job\Item\ItemReaderInterface;

final class CallbackReader implements ItemReaderInterface
{
    public function __construct(
        /**
         * @var \Closure(): iterable<mixed>
         */
        private readonly \Closure $callback,
    ) {
    }

    public function read(): iterable
    {
        return ($this->callback)();
    }
}
