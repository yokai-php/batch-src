<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration;

use Yokai\Batch\BatchStatus;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Job\JobWithChildJobs;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

final class JobWithFailingDummyChidlrenTest extends JobTestCase
{
    protected function createJob(JobExecutionStorageInterface $executionStorage): JobInterface
    {
        return new JobWithChildJobs(
            $executionStorage,
            self::createJobExecutor($executionStorage, [
                'prepare' => new class implements JobInterface {
                    public function execute(JobExecution $jobExecution): void
                    {
                        throw new \Exception('Critical dummy exception');
                    }
                },
                'do' => new class implements JobInterface {
                    public function execute(JobExecution $jobExecution): void
                    {
                        // this job should not be executed
                        $jobExecution->getSummary()->set('done', true);
                    }
                },
            ]),
            ['prepare', 'do'],
        );
    }

    protected function getJobName(): string
    {
        return 'job-with-failing-dummy-children';
    }

    protected function assertAgainstExecution(
        JobExecutionStorageInterface $jobExecutionStorage,
        JobExecution $jobExecution,
    ): void {
        parent::assertAgainstExecution($jobExecutionStorage, $jobExecution);

        self::assertSame(BatchStatus::Failed, $jobExecution->getStatus());

        $prepareChildExecution = $jobExecution->getChildExecution('prepare');
        self::assertSame(BatchStatus::Failed, $prepareChildExecution->getStatus());

        $doChildExecution = $jobExecution->getChildExecution('do');
        self::assertSame(BatchStatus::Abandoned, $doChildExecution->getStatus());
        self::assertNull($doChildExecution->getSummary()->get('done'));
    }
}
