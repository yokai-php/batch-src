<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Console;

/**
 * Knows how to run a Symfony console command asynchronously.
 */
interface CommandRunnerInterface
{
    /**
     * Execute a Symfony command asynchronously.
     *
     * @param array<string, mixed> $arguments
     */
    public function runAsync(string $commandName, string $logFilename, array $arguments = []): void;
}
