<?php

declare(strict_types=1);

namespace Yokai\Batch;

use DateTimeInterface;

/**
 * This value object represents a job execution result file with its path and optional start and end times.
 * It is used to encapsulate minimal information about a job execution result to avoid memory leaks.
 */
final class JobExecutionResultFile
{
    public function __construct(
        public readonly string $path,
        public readonly DateTimeInterface|null $jobStartTime,
        public readonly DateTimeInterface|null $jobEndTime,
    ) {
    }
}
