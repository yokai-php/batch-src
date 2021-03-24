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
use Yokai\Batch\Exception\CannotStoreJobExecutionException;
use Yokai\Batch\Exception\JobExecutionNotFoundException;
use Yokai\Batch\Exception\RuntimeException;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\Query;
use Yokai\Batch\Storage\QueryableJobExecutionStorageInterface;

final class DoctrineDBALJobExecutionStorage implements QueryableJobExecutionStorageInterface
{
    private const DEFAULT_OPTIONS = [
        'table' => 'yokai_batch_job_execution',
        'connection' => null,
    ];

    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @var string
     */
    private string $table;

    /**
     * @var JobExecutionRowNormalizer|null
     */
    private ?JobExecutionRowNormalizer $normalizer = null;

    public function __construct(ConnectionRegistry $doctrine, array $options)
    {
        $options = array_filter($options) + self::DEFAULT_OPTIONS;
        $options['connection'] = $options['connection'] ?? $doctrine->getDefaultConnectionName();

        $this->table = $options['table'];

        /** @var Connection $connection */
        $connection = $doctrine->getConnection($options['connection']);
        $this->connection = $connection;
    }

    public function createSchema(): void
    {
        $assetFilter = $this->connection->getConfiguration()->getSchemaAssetsFilter();
        $this->connection->getConfiguration()->setSchemaAssetsFilter(null);

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($this->connection->getSchemaManager()->createSchema(), $this->getSchema());

        foreach ($schemaDiff->toSaveSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->executeStatement($sql);
        }

        $this->connection->getConfiguration()->setSchemaAssetsFilter($assetFilter);
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
                $this->connection->update($this->table, $data, $this->identity($execution), $this->types());
            } else {
                $this->connection->insert($this->table, $data, $this->types());
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
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->table)
            ->where($qb->expr()->eq('job_name', ':jobName'));

        yield from $this->queryList(
            $qb->getSQL(),
            ['jobName' => $jobName],
            ['jobName' => Types::STRING]
        );
    }

    /**
     * @inheritDoc
     */
    public function query(Query $query): iterable
    {
        $queryParameters = [];
        $queryTypes = [];

        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->table);

        $names = $query->jobs();
        if (count($names) === 1) {
            $qb->andWhere($qb->expr()->eq('job_name', ':jobName'));
            $queryParameters['jobName'] = array_shift($names);
            $queryTypes['jobName'] = Types::STRING;
        } elseif (count($names) > 1) {
            $qb->andWhere($qb->expr()->in('job_name', ':jobNames'));
            $queryParameters['jobNames'] = $names;
            $queryTypes['jobNames'] = Connection::PARAM_STR_ARRAY;
        }

        $ids = $query->ids();
        if (count($ids) === 1) {
            $qb->andWhere($qb->expr()->eq('id', ':id'));
            $queryParameters['id'] = array_shift($ids);
            $queryTypes['id'] = Types::STRING;
        } elseif (count($ids) > 1) {
            $qb->andWhere($qb->expr()->in('id', ':ids'));
            $queryParameters['ids'] = $ids;
            $queryTypes['ids'] = Connection::PARAM_STR_ARRAY;
        }

        $statuses = $query->statuses();
        if (count($statuses) === 1) {
            $qb->andWhere($qb->expr()->eq('status', ':status'));
            $queryParameters['status'] = array_shift($statuses);
            $queryTypes['status'] = Types::INTEGER;
        } elseif (count($statuses) > 1) {
            $qb->andWhere($qb->expr()->in('status', ':statuses'));
            $queryParameters['statuses'] = $statuses;
            $queryTypes['statuses'] = Connection::PARAM_INT_ARRAY;
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

    private function identity(JobExecution $execution): array
    {
        return [
            'job_name' => $execution->getJobName(),
            'id' => $execution->getId(),
        ];
    }

    private function fetchRow(string $jobName, string $id): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->table)
            ->where($qb->expr()->eq('job_name', ':jobName'))
            ->andWhere($qb->expr()->eq('id', ':id'));

        /** @var Result $statement */
        $statement = $this->connection->executeQuery(
            $qb->getSQL(),
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
            $this->normalizer = new JobExecutionRowNormalizer($this->connection->getDatabasePlatform());
        }

        return $this->normalizer;
    }
}
