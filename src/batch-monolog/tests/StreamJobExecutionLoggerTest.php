<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Monolog;

use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Yokai\Batch\Bridge\Monolog\StreamJobExecutionLogger;

final class StreamJobExecutionLoggerTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = ARTIFACT_DIR . '/stream-job-execution-logger-' . \uniqid();
        \mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    public function testGetReferenceReturnsTheGivenReference(): void
    {
        $logger = $this->createLogger('test.log');

        self::assertSame('test.log', $logger->getReference());
    }

    public function testGetReferenceDoesNotReturnAbsolutePath(): void
    {
        $logger = $this->createLogger('exec-abc.log');

        self::assertStringNotContainsString($this->tmpDir, $logger->getReference());
    }

    public function testGetLogsIsEmptyWhenFileDoesNotExist(): void
    {
        $path = $this->tmpDir . '/nonexistent.log';
        $logger = new StreamJobExecutionLogger($path, 'nonexistent.log');

        self::assertSame([], \iterator_to_array($logger->getLogs()));
    }

    public function testGetLogsContentIsEmptyWhenFileDoesNotExist(): void
    {
        $path = $this->tmpDir . '/nonexistent.log';
        $logger = new StreamJobExecutionLogger($path, 'nonexistent.log');

        self::assertSame('', $logger->getLogsContent());
    }

    public function testLogWritesToFile(): void
    {
        $logger = $this->createLogger('test.log');

        $logger->info('hello world');

        self::assertFileExists($this->tmpDir . '/test.log');
        self::assertStringContainsString('hello world', $logger->getLogsContent());
    }

    public function testGetLogsYieldsLines(): void
    {
        $logger = $this->createLogger('test.log');

        $logger->info('first message');
        $logger->warning('second message');

        $lines = \iterator_to_array($logger->getLogs());

        self::assertCount(2, $lines);
        self::assertStringContainsString('first message', $lines[0]);
        self::assertStringContainsString('second message', $lines[1]);
    }

    public function testGetLogsContentReturnsFullContent(): void
    {
        $logger = $this->createLogger('test.log');

        $logger->error('something failed');

        self::assertStringContainsString('something failed', $logger->getLogsContent());
    }

    public function testGetLogsIsLazy(): void
    {
        $logger = $this->createLogger('test.log');

        $logger->debug('line one');
        $logger->debug('line two');
        $logger->debug('line three');

        $first = null;
        foreach ($logger->getLogs() as $line) {
            $first = $line;
            break;
        }

        self::assertNotNull($first);
        self::assertStringContainsString('line one', $first);
    }

    public function testProcessorIsApplied(): void
    {
        $logger = $this->createLogger('test.log', [new PsrLogMessageProcessor()]);

        $logger->info('value is {val}', ['val' => 'hello']);

        self::assertStringContainsString('value is hello', $logger->getLogsContent());
    }

    public function testFormatterIsApplied(): void
    {
        $logger = $this->createLogger('test.log', [], new JsonFormatter());

        $logger->info('json log');

        $decoded = \json_decode($logger->getLogsContent(), true);

        self::assertIsArray($decoded);
        self::assertSame('json log', $decoded['message']);
    }

    public function testGetReferenceReturnsNestedReferenceAsIs(): void
    {
        $logger = $this->createLogger('60/99/exec-1.log', subdir: '60/99');

        self::assertSame('60/99/exec-1.log', $logger->getReference());
    }

    public function testLogWritesToFileInSubdirectory(): void
    {
        $logger = $this->createLogger('60/99/exec-1.log', subdir: '60/99');

        $logger->info('nested message');

        self::assertFileExists($this->tmpDir . '/60/99/exec-1.log');
        self::assertStringContainsString('nested message', $logger->getLogsContent());
    }

    public function testGetLogsReadsFromNestedPath(): void
    {
        $logger = $this->createLogger('60/99/exec-1.log', subdir: '60/99');

        $logger->info('first line');
        $logger->warning('second line');

        $lines = \iterator_to_array($logger->getLogs());

        self::assertCount(2, $lines);
        self::assertStringContainsString('first line', $lines[0]);
        self::assertStringContainsString('second line', $lines[1]);
    }

    public function testGetLogsContentReadsFromNestedPath(): void
    {
        $logger = $this->createLogger('60/99/exec-1.log', subdir: '60/99');

        $logger->error('nested error');

        self::assertStringContainsString('nested error', $logger->getLogsContent());
    }

    private function createLogger(
        string $filename,
        array $processors = [],
        mixed $formatter = null,
        string $subdir = '',
    ): StreamJobExecutionLogger {
        if ($subdir !== '') {
            \mkdir($this->tmpDir . '/' . $subdir, 0755, true);
        }

        return new StreamJobExecutionLogger(
            $this->tmpDir . '/' . $filename,
            $filename,
            $processors,
            $formatter,
        );
    }
}
