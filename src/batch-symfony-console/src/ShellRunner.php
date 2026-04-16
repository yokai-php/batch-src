<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Console;

/**
 * Default {@see ShellRunnerInterface} implementation that delegates to PHP's {@see exec}.
 */
final class ShellRunner implements ShellRunnerInterface
{
    public function exec(string $command): void
    {
        \exec($command);
    }
}
