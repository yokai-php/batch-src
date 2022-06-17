<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Console;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Utility class that knows how to run command asynchronously.
 */
class CommandRunner
{
    private string $consolePath;
    private ?PhpExecutableFinder $phpLocator;

    public function __construct(
        string $binDir,
        private string $logDir,
        PhpExecutableFinder $phpLocator = null,
    ) {
        $this->consolePath = implode(DIRECTORY_SEPARATOR, [$binDir, 'console']);
        if (class_exists(PhpExecutableFinder::class)) {
            $phpLocator ??= new PhpExecutableFinder();
        }
        $this->phpLocator = $phpLocator;
    }

    /**
     * @phpstan-param array<string, mixed> $arguments
     */
    public function runAsync(string $commandName, string $logFilename, array $arguments = []): void
    {
        $this->exec(
            sprintf(
                '%s >> %s 2>&1 &',
                $this->buildCommand($commandName, $arguments),
                implode(DIRECTORY_SEPARATOR, [$this->logDir, $logFilename])
            )
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function exec(string $command): void
    {
        exec($command);
    }

    /**
     * @phpstan-param array<string, mixed> $arguments
     */
    private function buildCommand(string $commandName, array $arguments): string
    {
        return sprintf(
            '%s %s %s %s',
            $this->phpLocator ? $this->phpLocator->find() : 'php',
            $this->consolePath,
            $commandName,
            (string)(new ArrayInput($arguments))
        );
    }
}
