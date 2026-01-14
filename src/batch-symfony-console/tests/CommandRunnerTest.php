<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Console;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\PhpExecutableFinder;
use Yokai\Batch\Bridge\Symfony\Console\CommandRunner;

class CommandRunnerTest extends TestCase
{
    private function createRunner(): MockObject&CommandRunner
    {
        /** @var Stub&PhpExecutableFinder $phpLocator */
        $phpLocator = $this->createStub(PhpExecutableFinder::class);
        $phpLocator->method('find')->willReturn('/usr/bin/php');

        return $this->getMockBuilder(CommandRunner::class)
            ->onlyMethods(['exec'])
            ->setConstructorArgs(['/path/to/bin', '/path/to/logs', $phpLocator])
            ->getMock();
    }

    public function testRunAsync(): void
    {
        $runner = $this->createRunner();
        $runner->expects($this->once())
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
