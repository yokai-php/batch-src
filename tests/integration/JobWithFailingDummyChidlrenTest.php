<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration;

use Yokai\Batch\BatchStatus;
use Yokai\Batch\Job\JobExecutor;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Job\JobWithChildJobs;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

class JobWithFailingDummyChidlrenTest extends JobTestCase
{
    protected function createJob(JobExecutionStorageInterface $executionStorage): JobInterface
    {
        return new JobWithChildJobs(
            $executionStorage,
            new JobExecutor(
                self::createJobRegistry(
                    [
                        'prepare' => new class implements JobInterface
                        {
                            public function execute(JobExecution $jobExecution): void
                            {
                                throw new \Exception('Critical dummy exception');
                            }
                        },
                        'do' => new class implements JobInterface
                        {
                            public function execute(JobExecution $jobExecution): void
                            {
                                // this job should not be executed
                                $jobExecution->getSummary()->set('done', true);
                            }
                        },
                    ]
                ),
                $executionStorage,
                null
            ),
            ['prepare', 'do']
        );
    }

    protected function getJobName(): string
    {
        return 'job-with-failing-dummy-children';
    }

    protected function assertAgainstExecution(
        JobExecutionStorageInterface $jobExecutionStorage,
        JobExecution $jobExecution
    ): void {
        parent::assertAgainstExecution($jobExecutionStorage, $jobExecution);

        self::assertSame(BatchStatus::FAILED, $jobExecution->getStatus()->getValue());

        $prepareChildExecution = $jobExecution->getChildExecution('prepare');
        self::assertSame(BatchStatus::FAILED, $prepareChildExecution->getStatus()->getValue());

        $doChildExecution = $jobExecution->getChildExecution('do');
        self::assertSame(BatchStatus::ABANDONED, $doChildExecution->getStatus()->getValue());
        self::assertNull($doChildExecution->getSummary()->get('done'));
    }
}
