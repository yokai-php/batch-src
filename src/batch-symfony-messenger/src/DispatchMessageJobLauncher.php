<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Messenger;

use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * This {@see JobLauncherInterface} will execute job via a symfony message dispatch.
 */
final class DispatchMessageJobLauncher implements JobLauncherInterface
{
    public function __construct(
        private JobExecutionFactory $jobExecutionFactory,
        private JobExecutionStorageInterface $jobExecutionStorage,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function launch(string $name, array $configuration = []): JobExecution
    {
        // create and store execution before dispatching message
        // guarantee job execution exists if message bus transport is asynchronous
        $jobExecution = $this->jobExecutionFactory->create($name, $configuration);
        $configuration['_id'] ??= $jobExecution->getId();
        $jobExecution->setStatus(BatchStatus::PENDING);
        $this->jobExecutionStorage->store($jobExecution);

        try {
            // dispatch message
            $this->messageBus->dispatch(new LaunchJobMessage($name, $configuration));
        } catch (ExceptionInterface $exception) {
            // if a messenger exception occurs, it will be converted to job failure
            $jobExecution->setStatus(BatchStatus::FAILED);
            $jobExecution->addFailureException($exception);
            $this->jobExecutionStorage->store($jobExecution);

            return $jobExecution;
        }

        // re-fetch and return job execution from storage
        // if transport is synchronous, job execution may have been filled during execution

        return $this->jobExecutionStorage->retrieve($jobExecution->getJobName(), $jobExecution->getId());
    }
}
