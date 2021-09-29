<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\Fixtures;

use Yokai\Batch\Job\AbstractJob;
use Yokai\Batch\JobExecution;

final class DummyJob extends AbstractJob
{
    protected function doExecute(JobExecution $jobExecution): void
    {
        // dummy
    }
}
