<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\DBAL;

use Generator;
use RuntimeException;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALJobExecutionStorage;
use Yokai\Batch\Exception\CannotRemoveJobExecutionException;
use Yokai\Batch\Exception\CannotStoreJobExecutionException;
use Yokai\Batch\Exception\JobExecutionNotFoundException;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\Query;
use Yokai\Batch\Storage\QueryBuilder;
use Yokai\Batch\Test\Storage\JobExecutionStorageTestTrait;
use Yokai\Batch\Warning;

class DoctrineDBALJobExecutionStorageTest extends DoctrineDBALTestCase
{
    use JobExecutionStorageTestTrait;

    private function createStorage(array $options = []): DoctrineDBALJobExecutionStorage
    {
        return new DoctrineDBALJobExecutionStorage($this->doctrine, $options);
    }

    public function testCreateStandardTable(): void
    {
        $schemaManager = $this->connection->getSchemaManager();

        self::assertFalse($schemaManager->tablesExist(['yokai_batch_job_execution']));
        $this->createStorage()->createSchema();
        self::assertTrue($schemaManager->tablesExist(['yokai_batch_job_execution']));

        $columns = $schemaManager->listTableColumns('yokai_batch_job_execution');
        self::assertEquals(
            [
                'id',
                'job_name',
                'status',
                'parameters',
                'start_time',
                'end_time',
                'summary',
                'failures',
                'warnings',
                'child_executions',
                'logs',
            ],
            array_keys($columns)
        );
    }

    public function testCreateCustomTable(): void
    {
        $schemaManager = $this->connection->getSchemaManager();

        self::assertFalse($schemaManager->tablesExist(['acme_job_executions']));
        $this->createStorage(['table' => 'acme_job_executions'])->createSchema();
        self::assertTrue($schemaManager->tablesExist(['acme_job_executions']));

        $columns = $schemaManager->listTableColumns('acme_job_executions');
        self::assertEquals(
            [
                'id',
                'job_name',
                'status',
                'parameters',
                'start_time',
                'end_time',
                'summary',
                'failures',
                'warnings',
                'child_executions',
                'logs',
            ],
            array_keys($columns)
        );
    }

    public function testStoreInsert(): void
    {
        $storage = $this->createStorage();
        $storage->createSchema();

        $export = JobExecution::createRoot('123', 'export');
        $export->addChildExecution($extract = JobExecution::createChild($export, 'extract'));
        $export->addChildExecution($upload = JobExecution::createChild($export, 'upload'));
        $extract->addWarning(new Warning('Test warning'));
        $upload->addFailureException(new RuntimeException('Test failure'));
        $storage->store($export);

        $retrievedExport = $storage->retrieve('export', '123');
        self::assertSame('export', $retrievedExport->getJobName());
        self::assertSame('123', $retrievedExport->getId());
        self::assertSame(BatchStatus::PENDING, $retrievedExport->getStatus()->getValue());
        $retrievedExtract = $retrievedExport->getChildExecution('extract');
        self::assertNotNull($retrievedExtract);
        self::assertCount(1, $retrievedExtract->getWarnings());
        self::assertSame('Test warning', $retrievedExtract->getWarnings()[0]->getMessage());
        self::assertCount(0, $retrievedExtract->getFailures());
        $retrievedUpload = $retrievedExport->getChildExecution('upload');
        self::assertNotNull($retrievedUpload);
        self::assertCount(0, $retrievedUpload->getWarnings());
        self::assertCount(1, $retrievedUpload->getFailures());
        self::assertSame('Test failure', $retrievedUpload->getFailures()[0]->getMessage());
    }

    public function testStoreUpdate(): void
    {
        $storage = $this->createStorage();
        $storage->createSchema();
        $storage->store($execution = JobExecution::createRoot('123', 'export'));
        $execution->setStatus(BatchStatus::COMPLETED);
        $storage->store($execution);

        $retrievedExecution = $storage->retrieve('export', '123');
        self::assertSame('export', $retrievedExecution->getJobName());
        self::assertSame('123', $retrievedExecution->getId());
        self::assertSame(BatchStatus::COMPLETED, $retrievedExecution->getStatus()->getValue());
    }

    public function testStoreFailing(): void
    {
        $this->expectException(CannotStoreJobExecutionException::class);

        $storage = $this->createStorage();
        /** not calling {@see DoctrineDBALJobExecutionStorage::createSchema} will cause table to not exists */
        $storage->store(JobExecution::createRoot('123', 'export'));
    }

    public function testRemove(): void
    {
        $this->expectException(JobExecutionNotFoundException::class);

        $storage = $this->createStorage();
        $storage->createSchema();
        $storage->store($execution = JobExecution::createRoot('123', 'export'));
        $storage->remove($execution);

        $storage->retrieve('export', '123');
    }

    public function testRemoveFailing(): void
    {
        $this->expectException(CannotRemoveJobExecutionException::class);

        $storage = $this->createStorage();
        /** not calling {@see DoctrineDBALJobExecutionStorage::createSchema} will cause table to not exists */
        $storage->remove(JobExecution::createRoot('123', 'export'));
    }

    public function testRetrieve(): void
    {
        $storage = $this->createStorage();
        $storage->createSchema();
        $storage->store(JobExecution::createRoot('123', 'export'));
        $storage->store(JobExecution::createRoot('456', 'import'));

        $execution123 = $storage->retrieve('export', '123');
        self::assertSame('export', $execution123->getJobName());
        self::assertSame('123', $execution123->getId());

        $execution456 = $storage->retrieve('import', '456');
        self::assertSame('import', $execution456->getJobName());
        self::assertSame('456', $execution456->getId());
    }

    public function testRetrieveNotFound(): void
    {
        $this->expectException(JobExecutionNotFoundException::class);

        $storage = $this->createStorage();
        $storage->createSchema();
        $storage->store(JobExecution::createRoot('123', 'export'));

        $storage->retrieve('export', '456');
    }

    public function testRetrieveFailing(): void
    {
        $this->expectException(JobExecutionNotFoundException::class);

        $storage = $this->createStorage();
        /** not calling {@see DoctrineDBALJobExecutionStorage::createSchema} will cause table to not exists */
        $storage->retrieve('export', '456');
    }

    public function testList(): void
    {
        $storage = $this->createStorage();
        $storage->createSchema();
        $this->loadFixtures($storage);

        self::assertExecutionIds(['123'], $storage->list('export'));
        self::assertExecutionIds(['456', '789', '987'], $storage->list('import'));
    }

    /**
     * @dataProvider queries
     */
    public function testQuery(QueryBuilder $queryBuilder, array $expectedCouples): void
    {
        $storage = $this->createStorage();
        $storage->createSchema();
        $this->loadFixtures($storage);

        self::assertExecutions($expectedCouples, $storage->query($queryBuilder->getQuery()));
    }

    public function queries(): Generator
    {
        yield 'No filter' => [
            new QueryBuilder(),
            [
                ['export', '123'],
                ['import', '456'],
                ['import', '789'],
                ['import', '987'],
            ],
        ];
        yield 'Filter ids' => [
            (new QueryBuilder())
                ->ids(['123', '987']),
            [
                ['export', '123'],
                ['import', '987'],
            ],
        ];
        yield 'Filter job names' => [
            (new QueryBuilder())
                ->jobs(['export']),
            [
                ['export', '123'],
            ],
        ];
        yield 'Filter statuses' => [
            (new QueryBuilder())
                ->statuses([BatchStatus::FAILED]),
            [
                ['import', '456'],
            ],
        ];
        yield 'Order by start ASC' => [
            (new QueryBuilder())
                ->sort(Query::SORT_BY_START_ASC),
            [
                ['import', '987'],
                ['import', '789'],
                ['export', '123'],
                ['import', '456'],
            ],
        ];
        yield 'Order by start DESC' => [
            (new QueryBuilder())
                ->sort(Query::SORT_BY_START_DESC),
            [
                ['import', '456'],
                ['export', '123'],
                ['import', '789'],
                ['import', '987'],
            ],
        ];
        yield 'Order by end ASC' => [
            (new QueryBuilder())
                ->sort(Query::SORT_BY_END_ASC),
            [
                ['import', '789'],
                ['import', '987'],
                ['export', '123'],
                ['import', '456'],
            ],
        ];
        yield 'Order by end DESC' => [
            (new QueryBuilder())
                ->sort(Query::SORT_BY_END_DESC),
            [
                ['import', '456'],
                ['export', '123'],
                ['import', '987'],
                ['import', '789'],
            ],
        ];
    }

    public static function assertExecutionIds(array $ids, iterable $executions): void
    {
        $actualIds = [];
        /** @var JobExecution $execution */
        foreach ($executions as $execution) {
            self::assertInstanceOf(JobExecution::class, $execution);
            $actualIds[] = $execution->getId();
        }

        self::assertSame($ids, $actualIds);
    }

    private static function assertExecutions(array $expectedCouples, iterable $executions): void
    {
        $expected = [];
        foreach ($expectedCouples as [$jobName, $executionId]) {
            $expected[] = $jobName . '/' . $executionId;
        }

        $actual = [];
        /** @var JobExecution $execution */
        foreach ($executions as $execution) {
            $actual[] = $execution->getJobName() . '/' . $execution->getId();
        }

        self::assertSame($expected, $actual);
    }

    private function loadFixtures(DoctrineDBALJobExecutionStorage $storage): void
    {
        // completed export started at 2019-07-01 13:00 and ended at 2019-07-01 13:30
        $completedExport = JobExecution::createRoot('123', 'export', new BatchStatus(BatchStatus::COMPLETED));
        $completedExport->setStartTime(\DateTimeImmutable::createFromFormat(DATE_ISO8601, '2019-07-01T13:00:00+0200'));
        $completedExport->setEndTime(\DateTimeImmutable::createFromFormat(DATE_ISO8601, '2019-07-01T13:30:00+0200'));
        $storage->store($completedExport);

        // failed import started at 2019-07-01 17:30 and ended at 2019-07-01 18:30
        $failedImport = JobExecution::createRoot('456', 'import', new BatchStatus(BatchStatus::FAILED));
        $failedImport->setStartTime(\DateTimeImmutable::createFromFormat(DATE_ISO8601, '2019-07-01T17:30:00+0200'));
        $failedImport->setEndTime(\DateTimeImmutable::createFromFormat(DATE_ISO8601, '2019-07-01T18:30:00+0200'));
        $storage->store($failedImport);

        // running import started at 2019-06-30 22:00 and not ended
        $runningImport = JobExecution::createRoot('789', 'import', new BatchStatus(BatchStatus::RUNNING));
        $runningImport->setStartTime(\DateTimeImmutable::createFromFormat(DATE_ISO8601, '2019-06-30T22:00:00+0200'));
        $runningImport->getLogger()->debug('Importing things');
        $runningImport->getLogger()->info('Thing imported');
        $runningImport->getLogger()->warning('Weird thing imported');
        $storage->store($runningImport);

        // pending import not started and not ended
        $pendingImport = JobExecution::createRoot('987', 'import', new BatchStatus(BatchStatus::PENDING));
        $storage->store($pendingImport);
    }
}
