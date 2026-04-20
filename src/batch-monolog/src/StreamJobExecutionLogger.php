<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Monolog;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Yokai\Batch\Logger\JobExecutionLoggerInterface;

/**
 * A {@see JobExecutionLoggerInterface} implementation that writes logs to a stream via Monolog.
 *
 * Builds its own Monolog stack internally from the provided path and optional processors/formatter.
 * The reference stored alongside the job execution is a path relative to the configured directory,
 * keeping it portable across different environments.
 */
final class StreamJobExecutionLogger extends AbstractLogger implements JobExecutionLoggerInterface
{
    private readonly Logger $logger;

    /**
     * @param string                   $absolutePath Absolute path to the log file on disk.
     * @param string                   $reference    Relative path stored as the log reference.
     * @param list<ProcessorInterface> $processors   Monolog processors applied to every log record.
     * @param FormatterInterface|null  $formatter    Optional Monolog formatter for the stream handler.
     */
    public function __construct(
        private readonly string $absolutePath,
        private readonly string $reference,
        array $processors = [],
        FormatterInterface|null $formatter = null,
    ) {
        $handler = new StreamHandler($absolutePath);
        if ($formatter !== null) {
            $handler->setFormatter($formatter);
        }
        $this->logger = new Logger('yokai/batch', [$handler], $processors);
    }

    /**
     * @param Level|LogLevel::* $level
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getLogs(): iterable
    {
        $handle = @\fopen($this->absolutePath, 'r');
        if ($handle === false) {
            return;
        }

        try {
            while (($line = \fgets($handle)) !== false) {
                yield \rtrim($line, "\n\r");
            }
        } finally {
            \fclose($handle);
        }
    }

    public function getLogsContent(): string
    {
        $contents = @\file_get_contents($this->absolutePath);
        if ($contents === false) {
            return '';
        }

        return $contents;
    }
}
