<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Console;

use Yokai\Batch\BatchStatus;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * This {@see JobLauncherInterface} will execute job via an asynchronous symfony command.
 *
 * Example, if you call RunCommandJobLauncher::launch('import', ['foo'=>'bar']),
 * this command will run (with absolute pathes) :
 *
 *      php bin/console yokai:batch:run import '{"foo":"bar"}' >> var/log/batch_execute.log 2>&1 &
 */
final class RunCommandJobLauncher implements JobLauncherInterface
{
    public function __construct(
        private JobExecutionFactory $jobExecutionFactory,
        private CommandRunner $commandRunner,
        private JobExecutionStorageInterface $jobExecutionStorage,
        private string $logFilename = 'batch_execute.log',
    ) {
    }

    /**
     * @inheritdoc
     */
    public function launch(string $name, array $configuration = []): JobExecution
    {
        $jobExecution = $this->jobExecutionFactory->create($name, $configuration);
        $configuration['_id'] ??= $jobExecution->getId();
        $jobExecution->setStatus(BatchStatus::PENDING);
        $this->jobExecutionStorage->store($jobExecution);

        $this->commandRunner->runAsync(
            'yokai:batch:run',
            $this->logFilename,
            [
                'job' => $name,
                'configuration' => json_encode($configuration, JSON_THROW_ON_ERROR),
            ]
        );

        return $jobExecution;
    }
}
