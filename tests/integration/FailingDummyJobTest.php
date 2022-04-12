<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration;

use Yokai\Batch\BatchStatus;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\JobExecutionStorageInterface;
use Yokai\Batch\Warning;

class FailingDummyJobTest extends JobTestCase
{
    protected function createJob(JobExecutionStorageInterface $executionStorage): JobInterface
    {
        return new class implements JobInterface {
            public function execute(JobExecution $jobExecution): void
            {
                $jobExecution->addWarning(new Warning('WARNING! I am a dummy.'));
                $jobExecution->addFailureException(new \LogicException('Dummy...'));

                throw new \Exception('Critical dummy exception');
            }
        };
    }

    protected function getJobName(): string
    {
        return 'failing-dummy-job';
    }

    protected function assertAgainstExecution(
        JobExecutionStorageInterface $jobExecutionStorage,
        JobExecution $jobExecution
    ): void {
        parent::assertAgainstExecution($jobExecutionStorage, $jobExecution);

        self::assertTrue($jobExecution->getStatus()->is(BatchStatus::FAILED));
        self::assertCount(2, $jobExecution->getFailures());
        self::assertCount(1, $jobExecution->getWarnings());
    }
}
