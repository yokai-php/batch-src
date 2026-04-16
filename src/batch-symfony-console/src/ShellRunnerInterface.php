<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Console;

/**
 * Abstraction for executing shell commands.
 * Implement this interface in tests to capture commands instead of running them.
 */
interface ShellRunnerInterface
{
    /**
     * Execute a shell command.
     */
    public function exec(string $command): void;
}
