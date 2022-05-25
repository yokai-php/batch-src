<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Persistence\ConnectionRegistry;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Tests\Bridge\Doctrine\DBAL\Dummy\SingleConnectionRegistry;

abstract class DoctrineDBALTestCase extends TestCase
{
    protected Connection $connection;
    protected ConnectionRegistry $doctrine;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['url' => \getenv('DATABASE_URL')]);
        $this->doctrine = new SingleConnectionRegistry($this->connection);
    }

    protected function createTable(string $table, array $columns): void
    {
        $table = new Table($table);
        foreach ($columns as $name => $type) {
            $table->addColumn($name, $type);
        }
        $this->connection->getSchemaManager()->createTable($table);
    }

    protected function findAll(string $table): array
    {
        /** @var Result $results */
        $results = $this->connection->executeQuery(\sprintf('SELECT * FROM %s;', $table));

        return \array_map(
            fn(array $row) => \array_map('strval', $row),
            $results->fetchAllAssociative()
        );
    }
}
