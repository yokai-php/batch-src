<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Console;

use Yokai\Batch\BatchStatus;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

final class RunCommandJobLauncher implements JobLauncherInterface
{
    /**
     * @var JobExecutionFactory
     */
    private JobExecutionFactory $jobExecutionFactory;

    /**
     * @var CommandRunner
     */
    private CommandRunner $commandRunner;

    /**
     * @var string
     */
    private string $logFilename;

    /**
     * @var JobExecutionStorageInterface
     */
    private JobExecutionStorageInterface $jobExecutionStorage;

    public function __construct(
        JobExecutionFactory $jobExecutionFactory,
        CommandRunner $commandRunner,
        JobExecutionStorageInterface $jobExecutionStorage,
        string $logFilename = 'batch_execute.log'
    ) {
        $this->jobExecutionFactory = $jobExecutionFactory;
        $this->logFilename = $logFilename;
        $this->commandRunner = $commandRunner;
        $this->jobExecutionStorage = $jobExecutionStorage;
    }

    /**
     * @inheritdoc
     */
    public function launch(string $name, array $configuration = []): JobExecution
    {
        $jobExecution = $this->jobExecutionFactory->create($name, $configuration);
        $configuration['_id'] = $configuration['_id'] ?? $jobExecution->getId();
        $jobExecution->setStatus(BatchStatus::PENDING);
        $this->jobExecutionStorage->store($jobExecution);

        $this->commandRunner->runAsync(
            'yokai:batch:run',
            $this->logFilename,
            [
                'job' => $name,
                'configuration' => json_encode($configuration),
            ]
        );

        return $jobExecution;
    }
}
