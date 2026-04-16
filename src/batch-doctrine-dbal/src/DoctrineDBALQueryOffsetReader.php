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
final readonly class DoctrineDBALQueryOffsetReader implements ItemReaderInterface
{
    private Connection $connection;

    public function __construct(
        ConnectionRegistry $doctrine,
        private readonly string $sql,
        string|null $connection = null,
        private readonly int $batch = 500,
    ) {
        if (!\str_contains($this->sql, '{limit}') || !\str_contains($this->sql, '{offset}')) {
            throw new InvalidArgumentException(
                \sprintf('%s $sql argument must contains "{limit}" and "{offset}" for pagination.', __METHOD__),
            );
        }
        if ($this->batch <= 0) {
            throw new InvalidArgumentException(
                \sprintf('%s $batch argument must be a positive integer.', __METHOD__),
            );
        }

        $connectionName = $connection ?? $doctrine->getDefaultConnectionName();
        /** @var Connection $connection */
        $connection = $doctrine->getConnection($connectionName);
        $this->connection = $connection;
    }

    /**
     * @return Generator<array<string, string>>
     */
    public function read(): Generator
    {
        $iteration = 0;

        do {
            /** @var Result $statement */
            $statement = $this->connection->executeQuery(
                \strtr($this->sql, ['{limit}' => $this->batch, '{offset}' => $iteration * $this->batch]),
            );

            /** @var array<array<string, string>> $rows */
            $rows = $statement->fetchAllAssociative();

            yield from $rows;

            $iteration++;
        } while (\count($rows) === $this->batch);
    }
}
