<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Yokai\Batch\Exception\CannotStoreJobExecutionException;
use Yokai\Batch\Exception\JobExecutionNotFoundException;
use Yokai\Batch\Exception\RuntimeException;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\Query;
use Yokai\Batch\Storage\QueryableJobExecutionStorageInterface;

final class DoctrineDBALJobExecutionStorage implements QueryableJobExecutionStorageInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $table = 'yokai_batch_job_execution';

    /**
     * @var string
     */
    private $idCol = 'id';

    /**
     * @var string
     */
    private $jobNameCol = 'job_name';

    /**
     * @var string
     */
    private $statusCol = 'status';

    /**
     * @var string
     */
    private $parametersCol = 'parameters';

    /**
     * @var string
     */
    private $startTimeCol = 'start_time';

    /**
     * @var string
     */
    private $endTimeCol = 'end_time';

    /**
     * @var string
     */
    private $summaryCol = 'summary';

    /**
     * @var string
     */
    private $failuresCol = 'failures';

    /**
     * @var string
     */
    private $warningsCol = 'warnings';

    /**
     * @var string
     */
    private $childExecutionsCol = 'child_executions';

    /**
     * @var string
     */
    private $logsCol = 'logs';

    /**
     * @var array
     */
    private $types;

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var JobExecutionRowNormalizer|null
     */
    private $normalizer;

    public function __construct(Connection $connection, array $options)
    {
        $this->connection = $connection;

        $this->table = $options['table'] ?? $this->table;
        $this->idCol = $options['id_col'] ?? $this->idCol;
        $this->jobNameCol = $options['job_name_col'] ?? $this->jobNameCol;
        $this->statusCol = $options['status_col'] ?? $this->statusCol;
        $this->parametersCol = $options['parameters_col'] ?? $this->parametersCol;
        $this->startTimeCol = $options['start_time_col'] ?? $this->startTimeCol;
        $this->endTimeCol = $options['end_time_col'] ?? $this->endTimeCol;
        $this->summaryCol = $options['summary_col'] ?? $this->summaryCol;
        $this->failuresCol = $options['failures_col'] ?? $this->failuresCol;
        $this->warningsCol = $options['warnings_col'] ?? $this->warningsCol;
        $this->childExecutionsCol = $options['child_executions_col'] ?? $this->childExecutionsCol;
        $this->logsCol = $options['logs_col'] ?? $this->logsCol;

        $this->types = [
            $this->idCol => Types::STRING,
            $this->jobNameCol => Types::STRING,
            $this->statusCol => Types::INTEGER,
            $this->parametersCol => Types::JSON,
            $this->startTimeCol => Types::DATETIME_IMMUTABLE,
            $this->endTimeCol => Types::DATETIME_IMMUTABLE,
            $this->summaryCol => Types::JSON,
            $this->failuresCol => Types::JSON,
            $this->warningsCol => Types::JSON,
            $this->childExecutionsCol => Types::JSON,
            $this->logsCol => Types::TEXT,
        ];

        $this->schema = new Schema();
        $executionTable = $this->schema->createTable($this->table);
        $executionTable->addColumn($this->idCol, Types::STRING)
            ->setLength(128);
        $executionTable->addColumn($this->jobNameCol, Types::STRING)
            ->setLength(255);
        $executionTable->addColumn($this->statusCol, Types::INTEGER);
        $executionTable->addColumn($this->parametersCol, Types::JSON);
        $executionTable->addColumn($this->startTimeCol, Types::DATETIME_IMMUTABLE)
            ->setNotnull(false);
        $executionTable->addColumn($this->endTimeCol, Types::DATETIME_IMMUTABLE)
            ->setNotnull(false);
        $executionTable->addColumn($this->summaryCol, Types::JSON);
        $executionTable->addColumn($this->failuresCol, Types::JSON);
        $executionTable->addColumn($this->warningsCol, Types::JSON);
        $executionTable->addColumn($this->childExecutionsCol, Types::JSON);
        $executionTable->addColumn($this->logsCol, Types::TEXT);
        $executionTable->setPrimaryKey([$this->idCol]);
    }

    public function createSchema(): void
    {
        foreach ($this->createSchemaSql() as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    public function createSchemaSql(): array
    {
        $toSchema = $this->schema;

        $config = $this->connection->getConfiguration();
        $previousFilter = $config->getSchemaAssetsFilter();

        $config->setSchemaAssetsFilter(function ($asset) use ($previousFilter, $toSchema): bool {
            $assetName = $asset instanceof AbstractAsset ? $asset->getName() : $asset;

            return $toSchema->hasTable($assetName)
                || $toSchema->hasSequence($assetName)
                || ($previousFilter && $previousFilter($asset));
        });

        $fromSchema = $this->connection->getSchemaManager()->createSchema();

        $config->setSchemaAssetsFilter($previousFilter);

        return $fromSchema->getMigrateToSql($toSchema, $this->connection->getDatabasePlatform());
    }

    public function dropSchema(): void
    {
        foreach ($this->dropSchemaSql() as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    public function dropSchemaSql(): array
    {
        $platform = $this->connection->getDatabasePlatform()->getName();

        switch ($platform) {
            case 'mysql':
            case 'sqlite':
                $dropTable = <<<SQL
DROP TABLE {$this->table}
SQL;
                break;
            default:
                throw UnexpectedValueException::enum(['mysql', 'sqlite'], $platform, 'Platform is not supported.');
        }

        return [$dropTable];
    }

    /**
     * @inheritDoc
     */
    public function store(JobExecution $execution): void
    {
        try {
            $this->fetchRow($execution->getJobName(), $execution->getId());
            $stored = true;
        } catch (RuntimeException $exception) {
            $stored = false;
        }

        $data = $this->toRow($execution);

        try {
            if ($stored) {
                $this->connection->update($this->table, $data, $this->identity($execution), $this->types);
            } else {
                $this->connection->insert($this->table, $data, $this->types);
            }
        } catch (DBALException $exception) {
            throw new CannotStoreJobExecutionException($execution->getJobName(), $execution->getId(), $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function remove(JobExecution $execution): void
    {
        try {
            $this->connection->delete($this->table, $this->identity($execution));
        } catch (DBALException $exception) {
            throw new CannotStoreJobExecutionException($execution->getJobName(), $execution->getId(), $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function retrieve(string $jobName, string $executionId): JobExecution
    {
        try {
            $row = $this->fetchRow($jobName, $executionId);
        } catch (RuntimeException $exception) {
            throw new JobExecutionNotFoundException($jobName, $executionId, $exception);
        }

        return $this->fromRow($row);
    }

    /**
     * @inheritDoc
     */
    public function list(string $jobName): iterable
    {
        yield from $this->queryList(
            "SELECT * FROM {$this->table} WHERE {$this->jobNameCol} = :jobName",
            ['jobName' => $jobName],
            ['jobName' => Types::STRING]
        );
    }

    /**
     * @inheritDoc
     */
    public function query(Query $query): iterable
    {
        $queryConditions = [];
        $queryParameters = [];
        $queryTypes = [];

        $names = $query->jobs();
        if (count($names) === 1) {
            $queryConditions[] = "{$this->jobNameCol} = :jobName";
            $queryParameters['jobName'] = array_shift($names);
            $queryTypes['jobName'] = Types::STRING;
        } elseif (count($names) > 1) {
            $queryConditions[] = "{$this->jobNameCol} IN (:jobNames)";
            $queryParameters['jobNames'] = $names;
            $queryTypes['jobNames'] = Connection::PARAM_STR_ARRAY;
        }

        $ids = $query->ids();
        if (count($ids) === 1) {
            $queryConditions[] = "{$this->idCol} = :id";
            $queryParameters['id'] = array_shift($ids);
            $queryTypes['id'] = Types::STRING;
        } elseif (count($ids) > 1) {
            $queryConditions[] = "{$this->idCol} IN (:ids)";
            $queryParameters['ids'] = $ids;
            $queryTypes['ids'] = Connection::PARAM_STR_ARRAY;
        }

        $statuses = $query->statuses();
        if (count($statuses) === 1) {
            $queryConditions[] = "{$this->statusCol} = :status";
            $queryParameters['status'] = array_shift($statuses);
            $queryTypes['status'] = Types::INTEGER;
        } elseif (count($statuses) > 1) {
            $queryConditions[] = "{$this->statusCol} IN (:statuses)";
            $queryParameters['statuses'] = $statuses;
            $queryTypes['statuses'] = Connection::PARAM_INT_ARRAY;
        }

        $conditions = '';
        if (count($queryConditions) > 0) {
            $conditions = 'WHERE ' . implode(' AND ', $queryConditions);
        }

        $order = '';
        switch ($query->sort()) {
            case Query::SORT_BY_START_ASC:
                $order = "ORDER BY {$this->startTimeCol} ASC";
                break;
            case Query::SORT_BY_START_DESC:
                $order = "ORDER BY {$this->startTimeCol} DESC";
                break;
            case Query::SORT_BY_END_ASC:
                $order = "ORDER BY {$this->endTimeCol} ASC";
                break;
            case Query::SORT_BY_END_DESC:
                $order = "ORDER BY {$this->endTimeCol} DESC";
                break;
        }

        $count = $query->limit();
        $offset = $query->offset();
        $platform = $this->connection->getDatabasePlatform()->getName();
        switch ($platform) {
            case 'mysql':
            case 'sqlite':
                $limit = "LIMIT {$offset}, {$count}";
                break;
            default:
                throw UnexpectedValueException::enum(['mysql', 'sqlite'], $platform, 'Platform is not supported.');
        }

        yield from $this->queryList(
            "SELECT * FROM {$this->table} {$conditions} {$order} {$limit}",
            $queryParameters,
            $queryTypes
        );
    }

    private function identity(JobExecution $execution): array
    {
        return [
            $this->jobNameCol => $execution->getJobName(),
            $this->idCol => $execution->getId(),
        ];
    }

    private function fetchRow(string $jobName, string $id): array
    {
        /** @var Result $statement */
        $statement = $this->connection->executeQuery(
            "SELECT * FROM {$this->table} WHERE {$this->jobNameCol} = :jobName AND {$this->idCol} = :id",
            ['jobName' => $jobName, 'id' => $id],
            ['jobName' => Types::STRING, 'id' => Types::STRING]
        );
        $rows = $statement->fetchAllAssociative();
        switch (count($rows)) {
            case 1:
                $row = array_shift($rows);
                if (!\is_array($row)) {
                    throw UnexpectedValueException::type('array', $row);
                }

                return $row;
            case 0:
                throw new RuntimeException(
                    \sprintf('No row found for job %s#%s.', $jobName, $id)
                );
            default:
                throw new RuntimeException(
                    \sprintf('Expecting exactly 1 row for job %s#%s, but got %d.', $jobName, $id, count($rows))
                );
        }
    }

    private function queryList(string $query, array $parameters, array $types): iterable
    {
        /** @var Result $statement */
        $statement = $this->connection->executeQuery($query, $parameters, $types);

        while ($row = $statement->fetchAssociative()) {
            yield $this->fromRow($row);
        }

        $statement->free();
    }

    private function toRow(JobExecution $jobExecution): array
    {
        return $this->getNormalizer()->toRow($jobExecution);
    }

    private function fromRow(array $row): JobExecution
    {
        return $this->getNormalizer()->fromRow($row);
    }

    private function getNormalizer(): JobExecutionRowNormalizer
    {
        if ($this->normalizer === null) {
            $this->normalizer = new JobExecutionRowNormalizer(
                $this->idCol,
                $this->jobNameCol,
                $this->statusCol,
                $this->parametersCol,
                $this->startTimeCol,
                $this->endTimeCol,
                $this->summaryCol,
                $this->failuresCol,
                $this->warningsCol,
                $this->childExecutionsCol,
                $this->logsCol,
                $this->connection->getDatabasePlatform()->getDateTimeFormatString()
            );
        }

        return $this->normalizer;
    }
}
