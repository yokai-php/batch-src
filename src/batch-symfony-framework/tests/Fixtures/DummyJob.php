<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\Fixtures;

use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;

final class DummyJob implements JobInterface
{
    public function execute(JobExecution $jobExecution): void
    {
        // dummy
    }
}
