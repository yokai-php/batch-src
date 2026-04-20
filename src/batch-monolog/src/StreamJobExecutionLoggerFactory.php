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
     * @param string                   $directory       Directory where log files are stored.
     * @param list<ProcessorInterface> $processors      Monolog processors added to every logger instance.
     * @param FormatterInterface|null  $formatter       Optional Monolog formatter applied to the stream handler.
     * @param int                      $subDirectories  Number of subdirectory levels to create from the job execution id.
     * @param int                      $charsPerDirectory Number of characters from the job execution id used per subdirectory level.
     */
    public function __construct(
        private string $directory,
        private array $processors = [],
        private FormatterInterface|null $formatter = null,
        private int $subDirectories = 0,
        private int $charsPerDirectory = 0,
    ) {
    }

    public function create(string $jobExecutionId): JobExecutionLoggerInterface
    {
        $subdir = $this->subdir($jobExecutionId);
        $reference = ($subdir !== '' ? $subdir . \DIRECTORY_SEPARATOR : '') . $jobExecutionId . '.log';

        return $this->buildLogger($reference);
    }

    public function restore(string $logsReference): JobExecutionLoggerInterface
    {
        return $this->buildLogger($logsReference);
    }

    private function buildLogger(string $reference): StreamJobExecutionLogger
    {
        return new StreamJobExecutionLogger(
            absolutePath: $this->directory . \DIRECTORY_SEPARATOR . $reference,
            reference: $reference,
            processors: $this->processors,
            formatter: $this->formatter,
        );
    }

    /**
     * Splits the job execution id into subdirectory segments based on configured depth and segment length.
     * For example, with subDirectories=2 and charsPerDirectory=2, the id
     *   60996f72-4f54-4184-9268-35ffdecf0de6
     * is stored at:
     *   └─ 60/
     *     └─ 99/
     *       └─ 60996f72-4f54-4184-9268-35ffdecf0de6.log
     */
    private function subdir(string $id): string
    {
        $parts = [];
        for ($i = 0, $start = 0; $i < $this->subDirectories; $i++, $start += $this->charsPerDirectory) {
            $parts[] = \substr($id, $start, $this->charsPerDirectory);
        }

        return \implode(\DIRECTORY_SEPARATOR, $parts);
    }
}
