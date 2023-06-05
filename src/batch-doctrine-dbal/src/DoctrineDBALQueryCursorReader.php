<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\Persistence\ConnectionRegistry;
use Generator;
use Yokai\Batch\Exception\InvalidArgumentException;
use Yokai\Batch\Exception\LogicException;
use Yokai\Batch\Job\Item\ItemReaderInterface;

/**
 * This {@see ItemReaderInterface} executes an SQL query to a Doctrine connection,
 * and iterate over each result as an item.
 *
 * Use this reader when you are iterating over large data sets with lots of page,
 * and expecting good querying performance.
 * {@see https://medium.com/swlh/how-to-implement-cursor-pagination-like-a-pro-513140b65f32}
 *
 * The {@see DoctrineDBALQueryCursorReader::$sql} query must be something like
 *  SELECT id, email, name FROM user WHERE id > {after} ORDER BY id LIMIT {limit}
 * In that case, {@see DoctrineDBALQueryCursorReader::$column} argument should be "id",
 * and if id is a numeric column {@see DoctrineDBALQueryCursorReader::$start} should be 0.
 */
final class DoctrineDBALQueryCursorReader implements ItemReaderInterface
{
    private Connection $connection;

    public function __construct(
        ConnectionRegistry $doctrine,
        private string $sql,
        private string $column,
        private mixed $start,
        string $connection = null,
        private int $batch = 500,
    ) {
        if (!\str_contains($sql, '{after}') || !\str_contains($sql, '{limit}')) {
            throw new InvalidArgumentException(
                \sprintf('%s $sql argument must contains "{after}" and "{limit}" for pagination.', __METHOD__)
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
    }

    /**
     * @phpstan-return Generator<array<string, string>>
     */
    public function read(): Generator
    {
        $after = $this->start;

        do {
            /** @var Result $statement */
            $statement = $this->connection->executeQuery(
                \strtr($this->sql, ['{limit}' => $this->batch, '{after}' => $after])
            );

            /** @var array<array<string, string>> $rows */
            $rows = $statement->fetchAllAssociative();

            $lastRowIdx = \array_key_last($rows);
            if ($lastRowIdx !== null) {
                if (!isset($rows[$lastRowIdx][$this->column])) {
                    throw new LogicException(
                        \sprintf('Query must include "%s" column in results.', $this->column)
                    );
                }
                $after = $rows[$lastRowIdx][$this->column];
            }

            yield from $rows;
        } while ($lastRowIdx !== null);
    }
}
