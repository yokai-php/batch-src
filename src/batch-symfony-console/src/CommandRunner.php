<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Console;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Utility class that knows how to run command asynchronously.
 */
final class CommandRunner implements CommandRunnerInterface
{
    private readonly string $consolePath;
    private readonly null|PhpExecutableFinder $phpLocator;
    private readonly ShellRunnerInterface $shellRunner;

    public function __construct(
        string $binDir,
        private readonly string $logDir,
        PhpExecutableFinder|null $phpLocator = null,
        ShellRunnerInterface|null $shellRunner = null,
    ) {
        $this->consolePath = \implode(\DIRECTORY_SEPARATOR, [$binDir, 'console']);
        if (\class_exists(PhpExecutableFinder::class)) {
            $phpLocator ??= new PhpExecutableFinder();
        }
        $this->phpLocator = $phpLocator;
        $this->shellRunner = $shellRunner ?? new ShellRunner();
    }

    /**
     * Run a command asynchronously.
     *
     * @param array<string, mixed> $arguments
     */
    public function runAsync(string $commandName, string $logFilename, array $arguments = []): void
    {
        $this->shellRunner->exec(
            \sprintf(
                '%s >> %s 2>&1 &',
                $this->buildCommand($commandName, $arguments),
                \implode(DIRECTORY_SEPARATOR, [$this->logDir, $logFilename]),
            ),
        );
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function buildCommand(string $commandName, array $arguments): string
    {
        return \sprintf(
            '%s %s %s %s',
            $this->phpLocator?->find() ?? 'php',
            $this->consolePath,
            $commandName,
            (string)(new ArrayInput($arguments)),
        );
    }
}
