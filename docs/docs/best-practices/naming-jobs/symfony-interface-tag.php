<?php

declare(strict_types=1);

namespace App\Job;

use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;

final class ImportUserJob implements JobInterface, JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'import.user';
    }

    public function execute(JobExecution $jobExecution): void
    {
        // TODO: Implement execute() method.
    }
}
