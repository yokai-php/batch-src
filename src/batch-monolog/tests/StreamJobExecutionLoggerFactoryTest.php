<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Monolog;

use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Yokai\Batch\Bridge\Monolog\StreamJobExecutionLogger;
use Yokai\Batch\Bridge\Monolog\StreamJobExecutionLoggerFactory;
use Yokai\Batch\Exception\InvalidArgumentException;
use Yokai\Batch\Logger\InMemoryJobExecutionLogger;

final class StreamJobExecutionLoggerFactoryTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = ARTIFACT_DIR . '/stream-job-execution-logger-factory-' . \uniqid();
        \mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
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
    }

    public function testCreateWithSubDirectoriesBuildsNestedReference(): void
    {
        $factory = new StreamJobExecutionLoggerFactory(
            $this->tmpDir,
            subDirectories: 2,
            charsPerDirectory: 2,
        );

        $logger = $factory->create('60996f72-4f54-4184-9268-35ffdecf0de6');

        // xxh128('60996f72-4f54-4184-9268-35ffdecf0de6') starts with "4469"
        self::assertSame('44/69/60996f72-4f54-4184-9268-35ffdecf0de6.log', $logger->getReference());
    }

    public function testCreateWithSubDirectoriesWritesFileInSubdirectory(): void
    {
        $factory = new StreamJobExecutionLoggerFactory(
            $this->tmpDir,
            subDirectories: 2,
            charsPerDirectory: 2,
        );

        $logger = $factory->create('60996f72-4f54-4184-9268-35ffdecf0de6');
        $logger->info('nested log');

        self::assertFileExists($this->tmpDir . '/44/69/60996f72-4f54-4184-9268-35ffdecf0de6.log');
        self::assertStringContainsString('nested log', $logger->getLogsContent());
    }

    public function testRestoreWithSubDirectoryReferenceReadsFileContent(): void
    {
        $factory = new StreamJobExecutionLoggerFactory(
            $this->tmpDir,
            subDirectories: 2,
            charsPerDirectory: 2,
        );

        \mkdir($this->tmpDir . '/60/99', 0755, true);
        \file_put_contents($this->tmpDir . '/60/99/exec.log', "restored line\n");

        $logger = $factory->restore('60/99/exec.log');

        self::assertSame('60/99/exec.log', $logger->getReference());
        self::assertStringContainsString('restored line', $logger->getLogsContent());
    }

    public function testSubDirectoriesSpreadIdsSharingTheSamePrefix(): void
    {
        $factory = new StreamJobExecutionLoggerFactory(
            $this->tmpDir,
            subDirectories: 2,
            charsPerDirectory: 2,
        );

        // uniqid() values generated a few seconds apart share their first characters
        $first = $factory->create('6b06b4beac5f3')->getReference();
        $second = $factory->create('6b06b4c01d2e7')->getReference();

        self::assertNotSame(\dirname($first), \dirname($second));
    }

    public function testCharsPerDirectoryDefaultsToTwo(): void
    {
        $factory = new StreamJobExecutionLoggerFactory($this->tmpDir, subDirectories: 3);

        self::assertSame(
            '44/69/e7/60996f72-4f54-4184-9268-35ffdecf0de6.log',
            $factory->create('60996f72-4f54-4184-9268-35ffdecf0de6')->getReference(),
        );
    }

    #[DataProvider('invalidSubDirectories')]
    public function testInvalidSubDirectoriesConfiguration(int $subDirectories, int $charsPerDirectory): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StreamJobExecutionLoggerFactory(
            $this->tmpDir,
            subDirectories: $subDirectories,
            charsPerDirectory: $charsPerDirectory,
        );
    }

    public static function invalidSubDirectories(): \Generator
    {
        yield 'negative depth' => [-1, 2];
        yield 'no chars per directory' => [2, 0];
        yield 'negative chars per directory' => [2, -1];
        yield 'longer than the hash' => [5, 7];
    }

    public function testRestoreEmptyReferenceFallsBackToInMemoryLogger(): void
    {
        // A job execution stored by the in-memory logger before anything was logged,
        // for instance a pending execution waiting in a message queue when switching to this factory.
        $logger = (new StreamJobExecutionLoggerFactory($this->tmpDir))->restore('');

        self::assertInstanceOf(InMemoryJobExecutionLogger::class, $logger);

        $logger->info('Job started');

        self::assertStringContainsString('Job started', $logger->getLogsContent());
    }

    public function testRestoreInMemoryLogsReferenceFallsBackToInMemoryLogger(): void
    {
        // A job execution which logs were stored by the in-memory logger before switching to this factory.
        $logs = "[2026-11-25T10:00:00+00:00] INFO: Job started []\n[2026-11-25T10:00:01+00:00] INFO: Job ended []\n";

        $logger = (new StreamJobExecutionLoggerFactory($this->tmpDir))->restore($logs);

        self::assertInstanceOf(InMemoryJobExecutionLogger::class, $logger);
        self::assertSame($logs, $logger->getLogsContent());
        self::assertSame([], \glob($this->tmpDir . '/*'));
    }
}
