<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\Fixtures;

use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;

final class DummyJobWithName implements JobInterface, JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'export_orders_job';
    }

    public function execute(JobExecution $jobExecution): void
    {
        // dummy
    }
}
