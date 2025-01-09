<?php

declare(strict_types=1);

namespace App\Job;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;

#[AutoconfigureTag('yokai_batch.job')]
final class ImportUserJob implements JobInterface
{
    public function execute(JobExecution $jobExecution): void
    {
        // TODO: Implement execute() method.
    }
}
