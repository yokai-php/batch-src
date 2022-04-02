<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration;

use Yokai\Batch\BatchStatus;
use Yokai\Batch\Job\JobExecutor;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Job\JobWithChildJobs;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

class JobWithDummyChildrenTest extends JobTestCase
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
                                $jobExecution->getSummary()->set('done', true);
                            }
                        },
                        'do' => new class implements JobInterface
                        {
                            public function execute(JobExecution $jobExecution): void
                            {
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
        return 'job-with-dummy-children';
    }

    protected function assertAgainstExecution(
        JobExecutionStorageInterface $jobExecutionStorage,
        JobExecution $jobExecution
    ): void {
        parent::assertAgainstExecution($jobExecutionStorage, $jobExecution);

        self::assertSame(BatchStatus::COMPLETED, $jobExecution->getStatus()->getValue());

        $prepareChildExecution = $jobExecution->getChildExecution('prepare');
        self::assertSame(BatchStatus::COMPLETED, $prepareChildExecution->getStatus()->getValue());
        self::assertTrue($prepareChildExecution->getSummary()->get('done'));

        $doChildExecution = $jobExecution->getChildExecution('do');
        self::assertSame(BatchStatus::COMPLETED, $doChildExecution->getStatus()->getValue());
        self::assertTrue($doChildExecution->getSummary()->get('done'));
    }
}
