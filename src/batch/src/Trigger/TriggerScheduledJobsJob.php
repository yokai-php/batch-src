<?php

declare(strict_types=1);

namespace Yokai\Batch\Trigger;

use Yokai\Batch\Job\AbstractJob;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Trigger\Scheduler\SchedulerInterface;

final class TriggerScheduledJobsJob extends AbstractJob
{
    /**
     * @var SchedulerInterface[]
     * @phstan-var iterable<SchedulerInterface>
     */
    private iterable $schedulers;
    private JobLauncherInterface $jobLauncher;

    /**
     * @param SchedulerInterface[] $schedulers
     * @phstan-param iterable<SchedulerInterface> $schedulers
     */
    public function __construct(iterable $schedulers, JobLauncherInterface $jobLauncher)
    {
        $this->schedulers = $schedulers;
        $this->jobLauncher = $jobLauncher;
    }

    /**
     * @inheritdoc
     */
    protected function doExecute(JobExecution $jobExecution): void
    {
        $jobs = [];

        foreach ($this->schedulers as $scheduler) {
            foreach ($scheduler->get($jobExecution) as $scheduledJob) {
                $configuration = $scheduledJob->getParameters();
                if ($scheduledJob->getId() !== null) {
                    $configuration['_id'] = $scheduledJob->getId();
                }

                $scheduledJobExecution = $this->jobLauncher->launch($scheduledJob->getJobName(), $configuration);

                $jobs[] = $info = [
                    'scheduler' => \get_class($scheduler),
                    'job' => $scheduledJobExecution->getJobName(),
                    'id' => $scheduledJobExecution->getId(),
                ];
                $jobExecution->getLogger()->info('Launched scheduled job.', $info);
            }
        }

        $jobExecution->getSummary()->set('jobs', $jobs);
    }
}
