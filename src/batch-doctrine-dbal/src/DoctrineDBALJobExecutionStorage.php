<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ConnectionRegistry;
use Generator;
use Yokai\Batch\Exception\CannotRemoveJobExecutionException;
use Yokai\Batch\Exception\CannotStoreJobExecutionException;
use Yokai\Batch\Exception\JobExecutionNotFoundException;
use Yokai\Batch\Exception\RuntimeException;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\JobExecutionStorageInterface;
use Yokai\Batch\Storage\Query;
use Yokai\Batch\Storage\QueryableJobExecutionStorageInterface;

/**
 * This {@see JobExecutionStorageInterface} will store
 * {@see JobExecution} in an SQL database using doctrine/dbal.
 */
final class DoctrineDBALJobExecutionStorage implements QueryableJobExecutionStorageInterface
{
    private const DEFAULT_OPTIONS = [
        'table' => 'yokai_batch_job_execution',
        'connection' => null,
    ];

    private Connection $connection;
    private string $table;
    private JobExecutionRowNormalizer $normalizer;

    /**
     * @phpstan-param array{connection?: string, table?: string} $options
     */
    public function __construct(ConnectionRegistry $doctrine, array $options)
    {
        $options = array_filter($options) + self::DEFAULT_OPTIONS;
        $options['connection'] ??= $doctrine->getDefaultConnectionName();

        $this->table = $options['table'];

        /** @var Connection $connection */
        $connection = $doctrine->getConnection($options['connection']);
        $this->connection = $connection;
    }

    /**
     * Create required table for this storage.
     */
    public function createSchema(): void
    {
        $assetFilter = $this->connection->getConfiguration()->getSchemaAssetsFilter();
        $this->connection->getConfiguration()->setSchemaAssetsFilter(null);

        $schemaManager = method_exists($this->connection, 'createSchemaManager')
            ? $this->connection->createSchemaManager()
            : $this->connection->getSchemaManager();
        $comparator = method_exists($schemaManager, 'createComparator')
            ? $schemaManager->createComparator()
            : new Comparator();
        $fromSchema = method_exists($schemaManager, 'introspectSchema')
            ? $schemaManager->introspectSchema()
            : $schemaManager->createSchema();
        $toSchema = $this->getSchema();
        $schemaDiff = method_exists($comparator, 'compareSchemas')
            ? $comparator->compareSchemas($fromSchema, $toSchema)
            : $comparator->compare($fromSchema, $toSchema);
        $platform = $this->connection->getDatabasePlatform();
        $schemaDiffQueries = method_exists($platform, 'getAlterSchemaSQL')
            ? $platform->getAlterSchemaSQL($schemaDiff)
            : $schemaDiff->toSaveSql($platform);

        foreach ($schemaDiffQueries as $sql) {
            if (method_exists($this->connection, 'executeStatement')) {
                $this->connection->executeStatement($sql);
            } else {
                $this->connection->exec($sql);
            }
        }

        $this->connection->getConfiguration()->setSchemaAssetsFilter($assetFilter);
    }

    public function store(JobExecution $execution): void
    {
        try {
            try {
                $this->fetchRow($execution->getJobName(), $execution->getId());
                $stored = true;
            } catch (RuntimeException $exception) {
                $stored = false;
            }

            $data = $this->toRow($execution);

            if ($stored) {
                $this->connection->update($this->table, $data, $this->identity($execution), $this->types());
            } else {
                $this->connection->insert($this->table, $data, $this->types());
            }
        } catch (DBALException $exception) {
            throw new CannotStoreJobExecutionException($execution->getJobName(), $execution->getId(), $exception);
        }
    }

    public function remove(JobExecution $execution): void
    {
        try {
            $this->connection->delete($this->table, $this->identity($execution));
        } catch (DBALException $exception) {
            throw new CannotRemoveJobExecutionException($execution->getJobName(), $execution->getId(), $exception);
        }
    }

    public function retrieve(string $jobName, string $executionId): JobExecution
    {
        try {
            $row = $this->fetchRow($jobName, $executionId);
        } catch (RuntimeException | DBALException $exception) {
            throw new JobExecutionNotFoundException($jobName, $executionId, $exception);
        }

        return $this->fromRow($row);
    }

    public function list(string $jobName): iterable
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->table)
            ->where($qb->expr()->eq('job_name', ':jobName'));

        yield from $this->queryList($qb->getSQL(), ['jobName' => $jobName], ['jobName' => Types::STRING]);
    }

    public function query(Query $query): iterable
    {
        $queryParameters = [];
        $queryTypes = [];

        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->table);

        $names = $query->jobs();
        if (count($names) > 0) {
            $qb->andWhere($qb->expr()->in('job_name', ':jobNames'));
            $queryParameters['jobNames'] = $names;
            $queryTypes['jobNames'] = Connection::PARAM_STR_ARRAY;
        }

        $ids = $query->ids();
        if (count($ids) > 0) {
            $qb->andWhere($qb->expr()->in('id', ':ids'));
            $queryParameters['ids'] = $ids;
            $queryTypes['ids'] = Connection::PARAM_STR_ARRAY;
        }

        $statuses = $query->statuses();
        if (count($statuses) > 0) {
            $qb->andWhere($qb->expr()->in('status', ':statuses'));
            $queryParameters['statuses'] = $statuses;
            $queryTypes['statuses'] = Connection::PARAM_INT_ARRAY;
        }

        $startDateFrom = $query->startTime()?->getFrom();
        if ($startDateFrom) {
            $qb->andWhere($qb->expr()->gte('start_time', ':startDateFrom'));
            $queryParameters['startDateFrom'] = $startDateFrom;
        }
        $startDateTo = $query->startTime()?->getTo();
        if ($startDateTo) {
            $qb->andWhere($qb->expr()->lte('start_time', ':startDateTo'));
            $queryParameters['startDateTo'] = $startDateTo;
        }

        $endDateFrom = $query->endTime()?->getFrom();
        if ($endDateFrom) {
            $qb->andWhere($qb->expr()->gte('end_time', ':endDateFrom'));
            $queryParameters['endDateFrom'] = $endDateFrom;
        }
        $endDateTo = $query->endTime()?->getTo();
        if ($endDateTo) {
            $qb->andWhere($qb->expr()->lte('end_time', ':endDateTo'));
            $queryParameters['endDateTo'] = $endDateTo;
        }

        switch ($query->sort()) {
            case Query::SORT_BY_START_ASC:
                $qb->orderBy('start_time', 'asc');
                break;
            case Query::SORT_BY_START_DESC:
                $qb->orderBy('start_time', 'desc');
                break;
            case Query::SORT_BY_END_ASC:
                $qb->orderBy('end_time', 'asc');
                break;
            case Query::SORT_BY_END_DESC:
                $qb->orderBy('end_time', 'desc');
                break;
        }

        $qb->setMaxResults($query->limit());
        $qb->setFirstResult($query->offset());

        yield from $this->queryList($qb->getSQL(), $queryParameters, $queryTypes);
    }

    private function getSchema(): Schema
    {
        $schema = new Schema();
        $table = $schema->createTable($this->table);
        $table->addColumn('id', Types::STRING)
            ->setLength(128);
        $table->addColumn('job_name', Types::STRING)
            ->setLength(255);
        $table->addColumn('status', Types::INTEGER);
        $table->addColumn('parameters', Types::JSON);
        $table->addColumn('start_time', Types::DATETIME_IMMUTABLE)
            ->setNotnull(false);
        $table->addColumn('end_time', Types::DATETIME_IMMUTABLE)
            ->setNotnull(false);
        $table->addColumn('summary', Types::JSON);
        $table->addColumn('failures', Types::JSON);
        $table->addColumn('warnings', Types::JSON);
        $table->addColumn('child_executions', Types::JSON);
        $table->addColumn('logs', Types::TEXT);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['job_name']);
        $table->addIndex(['status']);
        $table->addIndex(['start_time']);
        $table->addIndex(['end_time']);

        return $schema;
    }

    /**
     * @phpstan-return array<string, string>
     */
    private function types(): array
    {
        return [
            'id' => Types::STRING,
            'job_name' => Types::STRING,
            'status' => Types::INTEGER,
            'parameters' => Types::JSON,
            'start_time' => Types::DATETIME_IMMUTABLE,
            'end_time' => Types::DATETIME_IMMUTABLE,
            'summary' => Types::JSON,
            'failures' => Types::JSON,
            'warnings' => Types::JSON,
            'child_executions' => Types::JSON,
            'logs' => Types::TEXT,
        ];
    }

    /**
     * @phpstan-return array<string, string>
     */
    private function identity(JobExecution $execution): array
    {
        return [
            'job_name' => $execution->getJobName(),
            'id' => $execution->getId(),
        ];
    }

    /**
     * @phpstan-return array<string, string>
     * @throws DBALException
     */
    private function fetchRow(string $jobName, string $id): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->table)
            ->where($qb->expr()->eq('job_name', ':jobName'))
            ->andWhere($qb->expr()->eq('id', ':id'))
            ->setMaxResults(1);

        /** @var Result $statement */
        $statement = $this->connection->executeQuery(
            $qb->getSQL(),
            ['jobName' => $jobName, 'id' => $id],
            ['jobName' => Types::STRING, 'id' => Types::STRING]
        );

        /** @var array<string, string>|null $row */
        $row = $statement->fetchAllAssociative()[0] ?? null;

        if ($row === null) {
            throw new RuntimeException(\sprintf('No row found for job %s#%s.', $jobName, $id));
        }

        return $row;
    }

    /**
     * @phpstan-param array<string, mixed>      $parameters
     * @phpstan-param array<string, int|string> $types
     *
     * @phpstan-return Generator<JobExecution>
     */
    private function queryList(string $query, array $parameters, array $types): Generator
    {
        /** @var Result $statement */
        $statement = $this->connection->executeQuery($query, $parameters, $types);

        while ($row = $statement->fetchAssociative()) {
            yield $this->fromRow($row);
        }

        $statement->free();
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    private function toRow(JobExecution $jobExecution): array
    {
        return $this->getNormalizer()->toRow($jobExecution);
    }

    /**
     * @phpstan-param array<string, mixed> $row
     */
    private function fromRow(array $row): JobExecution
    {
        return $this->getNormalizer()->fromRow($row);
    }

    private function getNormalizer(): JobExecutionRowNormalizer
    {
        $this->normalizer ??= new JobExecutionRowNormalizer($this->connection->getDatabasePlatform());

        return $this->normalizer;
    }
}
