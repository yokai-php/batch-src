<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Console;

use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\Symfony\Console\ShellRunner;

final class ShellRunnerTest extends TestCase
{
    public function testExec(): void
    {
        $outputFile = \sys_get_temp_dir() . '/yokai_batch_shell_runner_test_' . \uniqid() . '.txt';

        try {
            (new ShellRunner())->exec(\sprintf('echo hello > %s', \escapeshellarg($outputFile)));

            self::assertFileExists($outputFile);
            self::assertStringContainsString('hello', \file_get_contents($outputFile));
        } finally {
            if (\file_exists($outputFile)) {
                \unlink($outputFile);
            }
        }
    }
}
