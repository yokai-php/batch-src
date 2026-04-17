<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\Persistence\ConnectionRegistry;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Item\ItemWriterInterface;

/**
 * This {@see ItemWriterInterface} will insert all items to a single table,
 * via a Doctrine {@see Connection}.
 * All items must be arrays.
 * Column types are inferred from the table schema on first write when not provided explicitly.
 */
final class DoctrineDBALInsertWriter implements ItemWriterInterface
{
    private readonly Connection $connection;

    /**
     * @var array<int<0,max>, string|ParameterType|Type>|array<string, string|ParameterType|Type>|null
     */
    private array|null $types;

    /**
     * @param array<int<0,max>, string|ParameterType|Type>|array<string, string|ParameterType|Type>|null $types
     *   Column type hints for DBAL binding. When null, types are resolved lazily on first write
     *   via table schema introspection. Pass an empty array to disable type resolution entirely.
     */
    public function __construct(
        ConnectionRegistry $doctrine,
        /**
         * @var non-empty-string
         */
        private readonly string $table,
        string|null $connection = null,
        array|null $types = null,
    ) {
        $connection ??= $doctrine->getDefaultConnectionName();
        $connection = $doctrine->getConnection($connection);
        /** @var Connection $connection */
        $this->connection = $connection;
        $this->types = $types;
    }

    public function write(iterable $items): void
    {
        $this->types ??= $this->resolveTypes();

        foreach ($items as $item) {
            if (!\is_array($item)) {
                throw UnexpectedValueException::type('array', $item);
            }

            $this->connection->insert($this->table, $item, $this->types);
        }
    }

    /**
     * @return array<string, string|ParameterType|Type>
     */
    private function resolveTypes(): array
    {
        $types = [];
        $table = $this->connection->createSchemaManager()->introspectTableByUnquotedName($this->table);
        foreach ($table->getColumns() as $column) {
            $types[$column->getObjectName()->toString()] = $column->getType();
        }

        return $types;
    }
}
