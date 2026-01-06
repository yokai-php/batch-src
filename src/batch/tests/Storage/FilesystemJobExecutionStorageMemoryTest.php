<?php

declare(strict_types=1);

namespace Storage;

use PHPUnit\Framework\TestCase;
use Yokai\Batch\Serializer\JsonJobExecutionSerializer;
use Yokai\Batch\Storage\FilesystemJobExecutionStorage;
use Yokai\Batch\Storage\QueryBuilder;

class FilesystemJobExecutionStorageMemoryTest extends TestCase
{
    private const MAX_COUNT_MEMORY_USAGE = 10 * 1024 * 1024; // 10 MB
    private const MAX_ITERATION_MEMORY_USAGE = 110 * 1024 * 1024; // 130 MB

    private readonly FilesystemJobExecutionStorage $storage;
    private readonly int $memoryBefore;

    protected function setUp(): void
    {
        $this->storage = new FilesystemJobExecutionStorage(
            new JsonJobExecutionSerializer(),
            __DIR__.'/fixtures/filesystem-job-execution-large',
        );

        $this->memoryBefore = \memory_get_usage(true);
    }

    public function testNoOOMOnCount(): void
    {
        self::assertEquals(10, $this->storage->count((new QueryBuilder())->getQuery()));
        self::assertLessThan(self::MAX_COUNT_MEMORY_USAGE, $this->getMemoryUsed());
    }

    public function testNoOOMOnList(): void
    {
        foreach ($this->storage->list('memory') as $jobExecution) {
            self::assertNotEmpty($jobExecution->getJobName());
        }

        self::assertLessThan(self::MAX_ITERATION_MEMORY_USAGE, $this->getMemoryUsed());
    }

    public function testNoOOMOnQuery(): void
    {
        foreach ($this->storage->query((new QueryBuilder())->getQuery()) as $jobExecution) {
            self::assertNotEmpty($jobExecution->getJobName());
        }

        self::assertLessThan(self::MAX_ITERATION_MEMORY_USAGE, $this->getMemoryUsed());
    }

    private function getMemoryUsed(): int
    {
        return \memory_get_usage(true) - $this->memoryBefore;
    }
}
