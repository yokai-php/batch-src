<?php

declare(strict_types=1);

namespace Yokai\Batch\Job;

use Yokai\Batch\JobExecution;

/**
 * Covers {@see JobExecutionAwareInterface}.
 */
trait JobExecutionAwareTrait
{
    private JobExecution $jobExecution;

    /**
     * @inheritdoc
     */
    public function setJobExecution(JobExecution $jobExecution): void
    {
        $this->jobExecution = $jobExecution;
    }

    /**
     * Get root execution of current job execution.
     */
    public function getRootExecution(): JobExecution
    {
        return $this->jobExecution->getRootExecution();
    }
}
