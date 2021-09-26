<?php

declare(strict_types=1);

namespace Yokai\Batch\Job\Item\Exception;

use Throwable;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Warning;

/**
 * When an exception occurs when processing an item.
 */
final class SkipItemOnError implements SkipItemCauseInterface
{
    private Throwable $error;

    public function __construct(Throwable $error)
    {
        $this->error = $error;
    }

    /**
     * @inheritdoc
     */
    public function report(JobExecution $execution, $index, $item): void
    {
        $execution->getSummary()->increment('errored');
        $execution->addWarning(
            new Warning(
                'An error occurred.',
                [],
                [
                    'itemIndex' => $index,
                    'item' => $item,
                    'class' => \get_class($this->error),
                    'message' => $this->error->getMessage(),
                    'code' => $this->error->getCode(),
                    'trace' => $this->error->getTraceAsString(),
                ]
            )
        );
    }

    public function getError(): Throwable
    {
        return $this->error;
    }
}
