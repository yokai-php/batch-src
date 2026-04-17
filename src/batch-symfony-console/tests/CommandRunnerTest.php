<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Console;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\PhpExecutableFinder;
use Yokai\Batch\Bridge\Symfony\Console\CommandRunner;
use Yokai\Batch\Bridge\Symfony\Console\ShellRunnerInterface;

final class CommandRunnerTest extends TestCase
{
    private function createRunner(): array
    {
        /** @var MockObject&ShellRunnerInterface $shellRunner */
        $shellRunner = $this->createMock(ShellRunnerInterface::class);

        $phpLocator = $this->createStub(PhpExecutableFinder::class);
        $phpLocator->method('find')->willReturn('/usr/bin/php');

        $runner = new CommandRunner('/path/to/bin', '/path/to/logs', $phpLocator, $shellRunner);

        return [$runner, $shellRunner];
    }

    public function testRunAsync(): void
    {
        [$runner, $shellRunner] = $this->createRunner();

        $shellRunner->expects($this->once())
            ->method('exec')
            ->with(
                '/usr/bin/php /path/to/bin/console yokai:testing:test 1 ' .
                '\'{"json":["value",2]}"\' --opt --option=foo >> /path/to/logs/test.log 2>&1 &',
            );

        $runner->runAsync(
            'yokai:testing:test',
            'test.log',
            ['arg1' => '1', 'arg2' => '{"json":["value",2]}"', '--opt', '--option' => 'foo'],
        );
    }
}
