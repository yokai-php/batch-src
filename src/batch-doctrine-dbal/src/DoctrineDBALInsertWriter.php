<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Item\ItemWriterInterface;

/**
 * This {@see ItemWriterInterface} will insert all items to a single table,
 * via a Doctrine {@see Connection}.
 * All items must be arrays.
 */
final class DoctrineDBALInsertWriter implements ItemWriterInterface
{
    private Connection $connection;

    public function __construct(
        ConnectionRegistry $doctrine,
        private string $table,
        string $connection = null,
    ) {
        $connection ??= $doctrine->getDefaultConnectionName();
        /** @var Connection $connection */
        $connection = $doctrine->getConnection($connection);
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function write(iterable $items): void
    {
        foreach ($items as $item) {
            if (!\is_array($item)) {
                throw UnexpectedValueException::type('array', $item);
            }

            $this->connection->insert($this->table, $item);
        }
    }
}
