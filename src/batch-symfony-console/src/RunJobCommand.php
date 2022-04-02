<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\JobExecutionAccessor;
use Yokai\Batch\Job\JobExecutor;
use Yokai\Batch\JobExecution;

final class RunJobCommand extends Command
{
    protected static $defaultName = 'yokai:batch:run';

    public const EXIT_SUCCESS_CODE = 0;
    public const EXIT_ERROR_CODE = 1;
    public const EXIT_WARNING_CODE = 2;

    public function __construct(
        private JobExecutionAccessor $jobExecutionAccessor,
        private JobExecutor $jobExecutor,
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Execute any job.');
        $this->addArgument('job', InputArgument::REQUIRED, 'The job name to run');
        $this->addArgument('configuration', InputArgument::OPTIONAL, 'The job parameters as a JSON object');
        $this->addUsage('import');
        $this->addUsage('export \'{"toFile":"/path/to/file.xml"}\'');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $jobName */
        $jobName = $input->getArgument('job');
        /** @var string $configurationJson */
        $configurationJson = $input->getArgument('configuration') ?? '[]';

        $configuration = $this->decodeConfiguration($configurationJson);

        $execution = $this->jobExecutionAccessor->get($jobName, $configuration);
        $this->jobExecutor->execute($execution);

        $this->outputExecution($execution, $output);

        return $this->guessExecutionExitCode($execution);
    }

    private function guessExecutionExitCode(JobExecution $jobExecution): int
    {
        if ($jobExecution->getStatus()->is(BatchStatus::COMPLETED)) {
            if (count($jobExecution->getAllWarnings()) === 0) {
                return self::EXIT_SUCCESS_CODE;
            }

            return self::EXIT_WARNING_CODE;
        }

        return self::EXIT_ERROR_CODE;
    }

    private function outputExecution(JobExecution $jobExecution, OutputInterface $output): void
    {
        $jobName = $jobExecution->getJobName();
        if ($jobExecution->getStatus()->is(BatchStatus::COMPLETED)) {
            $warnings = $jobExecution->getAllWarnings();
            if (count($warnings)) {
                foreach ($warnings as $warning) {
                    $output->writeln(sprintf('<comment>%s</comment>', $warning), $output::VERBOSITY_VERBOSE);
                }
                $output->writeln(
                    sprintf('<comment>%s has been executed with %d warnings.</comment>', $jobName, count($warnings))
                );
            } else {
                $output->writeln(sprintf('<info>%s has been successfully executed.</info>', $jobName));
            }
        } else {
            $output->writeln(sprintf('<error>An error occurred during the %s execution.</error>', $jobName));
            foreach ($jobExecution->getAllFailures() as $failure) {
                $output->writeln(
                    sprintf(
                        '<error>Error #%s of class %s: %s</error>',
                        $failure->getCode(),
                        $failure->getClass(),
                        $failure
                    )
                );
                if ($failure->getTrace() !== null) {
                    $output->writeln(sprintf('<error>%s</error>', $failure->getTrace()), $output::VERBOSITY_VERBOSE);
                }
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     *
     * @phpstan-return array<string, mixed>
     */
    private function decodeConfiguration(string $data): array
    {
        $config = json_decode($data, true, 512, \JSON_THROW_ON_ERROR);
        if (\is_array($config)) {
            return $config;
        }

        throw UnexpectedValueException::type('array', $config);
    }
}
