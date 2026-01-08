<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Name;
use Doctrine\DBAL\Schema\Name\Identifier;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\NamedObject;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
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
use Yokai\Batch\Storage\SetupableJobExecutionStorageInterface;

/**
 * This {@see JobExecutionStorageInterface} will store
 * {@see JobExecution} in an SQL database using doctrine/dbal.
 */
final class DoctrineDBALJobExecutionStorage implements
    QueryableJobExecutionStorageInterface,
    SetupableJobExecutionStorageInterface
{
    private const DEFAULT_OPTIONS = [
        'table' => 'yokai_batch_job_execution',
        'connection' => null,
    ];

    private readonly Connection $connection;
    private readonly string $table;
    private JobExecutionRowNormalizer $normalizer;

    /**
     * @param array{connection?: string, table?: string} $options
     */
    public function __construct(ConnectionRegistry $doctrine, array $options)
    {
        $options = \array_filter($options) + self::DEFAULT_OPTIONS;
        $options['connection'] ??= $doctrine->getDefaultConnectionName();

        $this->table = $options['table'];

        /** @var Connection $connection */
        $connection = $doctrine->getConnection($options['connection']);
        $this->connection = $connection;
    }

    /**
     * Create required table for this storage.
     */
    public function setup(): void
    {
        $assetFilter = $this->connection->getConfiguration()->getSchemaAssetsFilter();
        $this->connection->getConfiguration()->setSchemaAssetsFilter(function (string|AbstractAsset $table) {
            $table = $table instanceof AbstractAsset ? $this->getAssetName($table) : $table;

            return $table === $this->table;
        });

        $schemaManager = $this->connection->createSchemaManager();
        $comparator = $schemaManager->createComparator();
        $fromSchema = $schemaManager->introspectSchema();
        $toSchema = $this->getSchema();
        $schemaDiff = $comparator->compareSchemas($fromSchema, $toSchema);
        $platform = $this->connection->getDatabasePlatform();
        $schemaDiffQueries = $platform->getAlterSchemaSQL($schemaDiff);

        foreach ($schemaDiffQueries as $sql) {
            $this->connection->executeStatement($sql);
        }

        $this->connection->getConfiguration()->setSchemaAssetsFilter($assetFilter);
    }

    public function store(JobExecution $execution): void
    {
        try {
            try {
                $this->fetchRow($execution->getJobName(), $execution->getId());
                $stored = true;
            } catch (RuntimeException) {
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
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->table);

        [$queryParameters, $queryTypes] = $this->addWheres($query, $qb);

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

    public function count(Query $query): int
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('count(*)')
            ->from($this->table);

        [$queryParameters, $queryTypes] = $this->addWheres($query, $qb);

        /** @var int $result */
        $result = $this->connection->executeQuery($qb->getSQL(), $queryParameters, $queryTypes)->fetchOne();

        return $result;
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
        if (\method_exists($table, 'addPrimaryKeyConstraint')) {
            $table->addPrimaryKeyConstraint(
                new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], false),
            );
        } else {
            $table->setPrimaryKey(['id']); // @codeCoverageIgnore deprecated method, untested in latest versions
        }
        $table->addIndex(['job_name']);
        $table->addIndex(['status']);
        $table->addIndex(['start_time']);
        $table->addIndex(['end_time']);

        return $schema;
    }

    /**
     * @return array<string, string>
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
     * @return array<string, string>
     */
    private function identity(JobExecution $execution): array
    {
        return [
            'job_name' => $execution->getJobName(),
            'id' => $execution->getId(),
        ];
    }

    /**
     * @return array<string, string>
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
            ['jobName' => Types::STRING, 'id' => Types::STRING],
        );

        /** @var array<string, string>|null $row */
        $row = $statement->fetchAllAssociative()[0] ?? null;

        if ($row === null) {
            throw new RuntimeException(\sprintf('No row found for job %s#%s.', $jobName, $id));
        }

        return $row;
    }

    /**
     * @param array<string, mixed>                     $parameters
     * @param array<string, string|ArrayParameterType> $types
     *
     * @return Generator<JobExecution>
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
     * @return array<string, mixed>
     */
    private function toRow(JobExecution $jobExecution): array
    {
        return $this->getNormalizer()->toRow($jobExecution);
    }

    /**
     * @param array<string, mixed> $row
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

    /**
     * @return array{
     *     array<string, mixed>,
     *     array<string, string|ArrayParameterType>,
     * }
     */
    private function addWheres(Query $query, QueryBuilder $qb): array
    {
        $queryParameters = [];
        $queryTypes = [];

        $names = $query->jobs();
        if (\count($names) > 0) {
            $qb->andWhere($qb->expr()->in('job_name', ':jobNames'));
            $queryParameters['jobNames'] = $names;
            $queryTypes['jobNames'] = ArrayParameterType::STRING;
        }

        $ids = $query->ids();
        if (\count($ids) > 0) {
            $qb->andWhere($qb->expr()->in('id', ':ids'));
            $queryParameters['ids'] = $ids;
            $queryTypes['ids'] = ArrayParameterType::STRING;
        }

        $statuses = $query->statuses();
        if (\count($statuses) > 0) {
            $qb->andWhere($qb->expr()->in('status', ':statuses'));
            $queryParameters['statuses'] = $statuses;
            $queryTypes['statuses'] = ArrayParameterType::INTEGER;
        }

        if ($query->startTime()) {
            $qb->andWhere($qb->expr()->isNotNull('start_time'));
        }
        $startDateFrom = $query->startTime()?->getFrom();
        if ($startDateFrom) {
            $qb->andWhere($qb->expr()->gte('start_time', ':startDateFrom'));
            $queryParameters['startDateFrom'] = $startDateFrom;
            $queryTypes['startDateFrom'] = Types::DATETIME_IMMUTABLE;
        }
        $startDateTo = $query->startTime()?->getTo();
        if ($startDateTo) {
            $qb->andWhere($qb->expr()->lte('start_time', ':startDateTo'));
            $queryParameters['startDateTo'] = $startDateTo;
            $queryTypes['startDateTo'] = Types::DATETIME_IMMUTABLE;
        }

        if ($query->endTime()) {
            $qb->andWhere($qb->expr()->isNotNull('start_time'));
        }
        $endDateFrom = $query->endTime()?->getFrom();
        if ($endDateFrom) {
            $qb->andWhere($qb->expr()->gte('end_time', ':endDateFrom'));
            $queryParameters['endDateFrom'] = $endDateFrom;
            $queryTypes['endDateFrom'] = Types::DATETIME_IMMUTABLE;
        }
        $endDateTo = $query->endTime()?->getTo();
        if ($endDateTo) {
            $qb->andWhere($qb->expr()->lte('end_time', ':endDateTo'));
            $queryParameters['endDateTo'] = $endDateTo;
            $queryTypes['endDateTo'] = Types::DATETIME_IMMUTABLE;
        }

        return [$queryParameters, $queryTypes];
    }

    /**
     * @param AbstractAsset<Name> $asset
     */
    private function getAssetName(AbstractAsset $asset): string
    {
        if ($asset instanceof NamedObject) {
            return $asset->getObjectName()->toString();
        }

        return $asset->getName(); // @codeCoverageIgnore deprecated method, untested in latest versions
    }
}
