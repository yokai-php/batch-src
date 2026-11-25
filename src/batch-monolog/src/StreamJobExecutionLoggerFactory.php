<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Monolog;

use Monolog\Formatter\FormatterInterface;
use Monolog\Processor\ProcessorInterface;
use Yokai\Batch\Exception\InvalidArgumentException;
use Yokai\Batch\Factory\JobExecutionLoggerFactoryInterface;
use Yokai\Batch\Logger\InMemoryJobExecutionLogger;
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
     * Subdirectory names are taken from this hash of the job execution id.
     */
    private const HASH_ALGORITHM = 'xxh128';
    private const HASH_LENGTH = 32;

    private const EXTENSION = '.log';

    /**
     * @param string                   $directory         Directory where log files are stored.
     * @param list<ProcessorInterface> $processors        Monolog processors added to every logger instance.
     * @param FormatterInterface|null  $formatter         Optional Monolog formatter applied to the stream handler.
     * @param int                      $subDirectories    Number of subdirectory levels (0 stores all files flat in the directory).
     * @param int                      $charsPerDirectory Number of characters in each subdirectory name.
     */
    public function __construct(
        private string $directory,
        private array $processors = [],
        private FormatterInterface|null $formatter = null,
        private int $subDirectories = 0,
        private int $charsPerDirectory = 2,
    ) {
        if ($subDirectories < 0) {
            throw new InvalidArgumentException(
                \sprintf('%s $subDirectories argument must be a positive integer or 0.', __METHOD__),
            );
        }
        if ($subDirectories > 0 && $charsPerDirectory <= 0) {
            throw new InvalidArgumentException(
                \sprintf('%s $charsPerDirectory argument must be a positive integer.', __METHOD__),
            );
        }
        if ($subDirectories * $charsPerDirectory > self::HASH_LENGTH) {
            throw new InvalidArgumentException(
                \sprintf(
                    '%s $subDirectories * $charsPerDirectory must not exceed %d.',
                    __METHOD__,
                    self::HASH_LENGTH,
                ),
            );
        }
    }

    public function create(string $jobExecutionId): JobExecutionLoggerInterface
    {
        $subdir = $this->subdir($jobExecutionId);
        $reference = ($subdir !== '' ? $subdir . \DIRECTORY_SEPARATOR : '') . $jobExecutionId . self::EXTENSION;

        return $this->buildLogger($reference);
    }

    public function restore(string $logsReference): JobExecutionLoggerInterface
    {
        // References created by this factory are always paths to a ".log" file.
        // Anything else was created by another factory, for instance logs stored by the in-memory logger
        // before the application switched to this one: keep these job executions readable and writable.
        if (!\str_ends_with($logsReference, self::EXTENSION) || \str_contains($logsReference, "\n")) {
            return new InMemoryJobExecutionLogger($logsReference);
        }

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
     * Splits a hash of the job execution id into subdirectory segments, based on configured depth and segment length.
     * Hashing spreads files evenly, even when ids start with a timestamp (uniqid, ULID, time based UUID).
     * For example, with subDirectories=2 and charsPerDirectory=2, the id
     *   60996f72-4f54-4184-9268-35ffdecf0de6
     * which hash starts with "4469", is stored at:
     *   └─ 44/
     *     └─ 69/
     *       └─ 60996f72-4f54-4184-9268-35ffdecf0de6.log
     */
    private function subdir(string $id): string
    {
        if ($this->subDirectories === 0) {
            return '';
        }

        $hash = \hash(self::HASH_ALGORITHM, $id);

        $parts = [];
        for ($level = 0; $level < $this->subDirectories; $level++) {
            $parts[] = \substr($hash, $level * $this->charsPerDirectory, $this->charsPerDirectory);
        }

        return \implode(\DIRECTORY_SEPARATOR, $parts);
    }
}
