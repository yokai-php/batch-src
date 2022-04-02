<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration;

use Yokai\Batch\BatchStatus;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

class DummyJobTest extends JobTestCase
{
    protected function createJob(JobExecutionStorageInterface $executionStorage): JobInterface
    {
        return new class implements JobInterface {
            public function execute(JobExecution $jobExecution): void
            {
                $jobExecution->getSummary()->set('done', true);
            }
        };
    }

    protected function getJobName(): string
    {
        return 'dummy-job';
    }

    protected function assertAgainstExecution(
        JobExecutionStorageInterface $jobExecutionStorage,
        JobExecution $jobExecution
    ): void {
        parent::assertAgainstExecution($jobExecutionStorage, $jobExecution);

        self::assertSame(BatchStatus::COMPLETED, $jobExecution->getStatus()->getValue());
        self::assertTrue($jobExecution->getSummary()->get('done'));
    }
}
