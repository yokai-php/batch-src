<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\Fixtures;

use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\AbstractJob;
use Yokai\Batch\JobExecution;

final class DummyJobWithName extends AbstractJob implements JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'export_orders_job';
    }

    protected function doExecute(JobExecution $jobExecution): void
    {
        // dummy
    }
}
