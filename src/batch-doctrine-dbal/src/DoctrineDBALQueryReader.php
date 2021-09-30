<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\Persistence\ConnectionRegistry;
use Generator;
use Yokai\Batch\Exception\InvalidArgumentException;
use Yokai\Batch\Job\Item\ItemReaderInterface;

/**
 * This {@see ItemReaderInterface} executes an SQL query to a Doctrine connection,
 * and iterate over each result as an item.
 */
final class DoctrineDBALQueryReader implements ItemReaderInterface
{
    private Connection $connection;
    private string $sql;
    private int $batch;

    public function __construct(ConnectionRegistry $doctrine, string $sql, string $connection = null, int $batch = 500)
    {
        if (\mb_strpos($sql, '{limit}') === false || \mb_strpos($sql, '{offset}') === false) {
            throw new InvalidArgumentException(
                \sprintf('%s $sql argument must contains "{limit}" and "{offset}" for pagination.', __METHOD__)
            );
        }
        if ($batch <= 0) {
            throw new InvalidArgumentException(
                \sprintf('%s $batch argument must be a positive integer.', __METHOD__)
            );
        }

        $connection ??= $doctrine->getDefaultConnectionName();
        /** @var Connection $connection */
        $connection = $doctrine->getConnection($connection);
        $this->connection = $connection;
        $this->sql = $sql;
        $this->batch = $batch;
    }

    /**
     * @inheritdoc
     * @phpstan-return Generator<array<string, string>>
     */
    public function read(): Generator
    {
        $iteration = 0;

        do {
            /** @var Result $statement */
            $statement = $this->connection->executeQuery(
                \strtr($this->sql, ['{limit}' => $this->batch, '{offset}' => $iteration * $this->batch])
            );

            $rows = $statement->fetchAllAssociative();

            yield from $rows;

            $iteration++;
        } while (\count($rows) === $this->batch);
    }
}
