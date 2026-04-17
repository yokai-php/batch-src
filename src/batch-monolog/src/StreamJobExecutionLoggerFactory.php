<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Monolog;

use Monolog\Formatter\FormatterInterface;
use Monolog\Processor\ProcessorInterface;
use Yokai\Batch\Factory\JobExecutionLoggerFactoryInterface;
use Yokai\Batch\Logger\JobExecutionLoggerInterface;

/**
 * A {@see JobExecutionLoggerFactoryInterface} implementation that writes logs to a file via Monolog's StreamHandler.
 *
 * Each job execution gets its own file named after the job execution id.
 * Processors and an optional formatter allow the caller to customise the Monolog stack.
 */
final readonly class StreamJobExecutionLoggerFactory implements JobExecutionLoggerFactoryInterface
{
    /**
     * @param string                   $directory  Directory where log files are stored.
     * @param list<ProcessorInterface> $processors Monolog processors added to every logger instance.
     * @param FormatterInterface|null  $formatter  Optional Monolog formatter applied to the stream handler.
     */
    public function __construct(
        private string $directory,
        private array $processors = [],
        private FormatterInterface|null $formatter = null,
    ) {
    }

    public function create(string $jobExecutionId): JobExecutionLoggerInterface
    {
        return $this->createLogger("{$jobExecutionId}.log");
    }

    public function restore(string $logsReference): JobExecutionLoggerInterface
    {
        return $this->createLogger($logsReference);
    }

    private function createLogger(string $logsReference): StreamJobExecutionLogger
    {
        return new StreamJobExecutionLogger(
            absolutePath: $this->directory . \DIRECTORY_SEPARATOR . $logsReference,
            reference: $logsReference,
            processors: $this->processors,
            formatter: $this->formatter,
        );
    }
}
