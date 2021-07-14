<?php

declare(strict_types=1);

namespace Yokai\Batch\Trigger\Scheduler;

interface SchedulerInterface
{
    /**
     * @return ScheduledJob[]
     * @phpstan-return iterable<ScheduledJob>
     */
    public function get(): iterable;
}
