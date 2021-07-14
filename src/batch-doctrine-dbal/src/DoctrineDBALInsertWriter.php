<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Yokai\Batch\Job\Item\ItemWriterInterface;

final class DoctrineDBALInsertWriter implements ItemWriterInterface
{
    private Connection $connection;
    private string $table;

    public function __construct(ConnectionRegistry $doctrine, string $table, string $connection = null)
    {
        $connection ??= $doctrine->getDefaultConnectionName();
        /** @var Connection $connection */
        $connection = $doctrine->getConnection($connection);
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * @inheritdoc
     */
    public function write(iterable $items): void
    {
        foreach ($items as $item) {
            $this->connection->insert($this->table, $item);
        }
    }
}
