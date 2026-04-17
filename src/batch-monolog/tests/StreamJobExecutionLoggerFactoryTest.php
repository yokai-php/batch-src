<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Monolog;

use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\Monolog\StreamJobExecutionLogger;
use Yokai\Batch\Bridge\Monolog\StreamJobExecutionLoggerFactory;

final class StreamJobExecutionLoggerFactoryTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = \sys_get_temp_dir() . '/yokai-batch-monolog-factory-test-' . \uniqid();
        \mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->tmpDir . '/*') ?: [] as $file) {
            \unlink($file);
        }
        \rmdir($this->tmpDir);
    }

    public function testCreateReturnsStreamJobExecutionLogger(): void
    {
        $factory = new StreamJobExecutionLoggerFactory($this->tmpDir);

        self::assertInstanceOf(StreamJobExecutionLogger::class, $factory->create('exec-1'));
    }

    public function testCreateUsesJobExecutionIdAsRelativeReference(): void
    {
        $factory = new StreamJobExecutionLoggerFactory($this->tmpDir);

        $logger = $factory->create('exec-abc');

        self::assertSame('exec-abc.log', $logger->getReference());
    }

    public function testCreateGeneratesDistinctReferencePerExecutionId(): void
    {
        $factory = new StreamJobExecutionLoggerFactory($this->tmpDir);

        $first = $factory->create('exec-1');
        $second = $factory->create('exec-2');

        self::assertNotSame($first->getReference(), $second->getReference());
    }

    public function testCreatedLoggerIsInitiallyEmpty(): void
    {
        $factory = new StreamJobExecutionLoggerFactory($this->tmpDir);

        $logger = $factory->create('exec-empty');

        self::assertSame([], \iterator_to_array($logger->getLogs()));
        self::assertSame('', $logger->getLogsContent());
    }

    public function testCreateCreatesDirectoryIfMissing(): void
    {
        $nestedDir = $this->tmpDir . '/nested/dir';
        $factory = new StreamJobExecutionLoggerFactory($nestedDir);

        $logger = $factory->create('exec-1');
        $logger->info('hello');

        self::assertDirectoryExists($nestedDir);

        \unlink($nestedDir . '/exec-1.log');
        \rmdir($nestedDir);
        \rmdir(\dirname($nestedDir));
    }

    public function testRestoreReturnsLoggerWithGivenReference(): void
    {
        $factory = new StreamJobExecutionLoggerFactory($this->tmpDir);

        $logger = $factory->restore('existing.log');

        self::assertSame('existing.log', $logger->getReference());
    }

    public function testRestoreReadsExistingFileContent(): void
    {
        $factory = new StreamJobExecutionLoggerFactory($this->tmpDir);

        \file_put_contents($this->tmpDir . '/existing.log', "line one\nline two\n");

        $logger = $factory->restore('existing.log');

        self::assertStringContainsString('line one', $logger->getLogsContent());
        self::assertStringContainsString('line two', $logger->getLogsContent());
    }

    public function testProcessorIsPassedToLogger(): void
    {
        $factory = new StreamJobExecutionLoggerFactory(
            $this->tmpDir,
            [new PsrLogMessageProcessor()],
        );

        $logger = $factory->create('exec-proc');
        $logger->info('value is {val}', ['val' => 'hello']);

        self::assertStringContainsString('value is hello', $logger->getLogsContent());

        \unlink($this->tmpDir . '/exec-proc.log');
    }

    public function testFormatterIsPassedToLogger(): void
    {
        $factory = new StreamJobExecutionLoggerFactory(
            $this->tmpDir,
            [],
            new JsonFormatter(),
        );

        $logger = $factory->create('exec-fmt');
        $logger->info('json log');

        $decoded = \json_decode($logger->getLogsContent(), true);

        self::assertIsArray($decoded);
        self::assertSame('json log', $decoded['message']);

        \unlink($this->tmpDir . '/exec-fmt.log');
    }
}
