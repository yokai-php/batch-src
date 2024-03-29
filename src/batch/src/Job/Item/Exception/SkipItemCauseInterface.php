<?php

declare(strict_types=1);

namespace Yokai\Batch\Job\Item\Exception;

use Yokai\Batch\Job\Item\ItemJob;
use Yokai\Batch\Job\Item\ItemProcessorInterface;
use Yokai\Batch\JobExecution;

/**
 * The reason why the item was skipped, attached to a {@see SkipItemException}.
 */
interface SkipItemCauseInterface
{
    /**
     * Record the cause of item skip to a {@see JobExecution}.
     * Called by {@see ItemJob} when {@see SkipItemException}
     * is thrown by any {@see ItemProcessorInterface}.
     */
    public function report(JobExecution $execution, int|string $index, mixed $item): void;
}
