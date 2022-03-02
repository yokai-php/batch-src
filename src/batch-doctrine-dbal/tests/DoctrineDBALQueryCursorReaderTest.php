<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Types\Types;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALQueryCursorReader;
use Yokai\Batch\Exception\InvalidArgumentException;
use Yokai\Batch\Exception\LogicException;

class DoctrineDBALQueryCursorReaderTest extends DoctrineDBALTestCase
{
    public function test(): void
    {
        $this->createTable('user', [
            'id' => Types::INTEGER,
            'name' => Types::STRING,
        ]);
        $this->connection->insert('user', ['id' => 1, 'name' => 'Anthony Curtis']);
        $this->connection->insert('user', ['id' => 2, 'name' => 'Paige Pritchard']);
        $this->connection->insert('user', ['id' => 3, 'name' => 'Georgia Tyler']);
        $this->connection->insert('user', ['id' => 4, 'name' => 'Annett Wechsler']);
        $this->connection->insert('user', ['id' => 5, 'name' => 'Sabine Waechter']);
        $this->connection->insert('user', ['id' => 6, 'name' => 'Patrick Hahn']);
        $this->connection->insert('user', ['id' => 7, 'name' => 'Richard Bazinet']);
        $this->connection->insert('user', ['id' => 8, 'name' => 'Jacques Lafond']);
        $this->connection->insert('user', ['id' => 9, 'name' => 'Benoît de Brisay']);

        $reader = new DoctrineDBALQueryCursorReader(
            $this->doctrine,
            'SELECT id, name FROM user WHERE id > {after} LIMIT {limit};',
            'id',
            0,
            null,
            4 // we will take 4 records at once : 3 queries expected
        );

        $read = \array_map(
            fn(array $row) => \array_map('strval', $row),
            \iterator_to_array($reader->read(), false)
        );

        self::assertSame([
            ['id' => '1', 'name' => 'Anthony Curtis'],
            ['id' => '2', 'name' => 'Paige Pritchard'],
            ['id' => '3', 'name' => 'Georgia Tyler'],
            ['id' => '4', 'name' => 'Annett Wechsler'],
            ['id' => '5', 'name' => 'Sabine Waechter'],
            ['id' => '6', 'name' => 'Patrick Hahn'],
            ['id' => '7', 'name' => 'Richard Bazinet'],
            ['id' => '8', 'name' => 'Jacques Lafond'],
            ['id' => '9', 'name' => 'Benoît de Brisay'],
        ], $read);
    }

    public function testQueryMustContainsAfterPlaceholder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DoctrineDBALQueryCursorReader(
            $this->doctrine,
            'SELECT * FROM some table WHERE id > 0 LIMIT {limit};',
            'id',
            0
        );
    }

    public function testQueryMustContainsLimitPlaceholder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DoctrineDBALQueryCursorReader(
            $this->doctrine,
            'SELECT * FROM some table WHERE id > {after} LIMIT 1;',
            'id',
            0
        );
    }

    public function testBatchMustBePositiveInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DoctrineDBALQueryCursorReader(
            $this->doctrine,
            'SELECT id, name FROM user WHERE id > {after} LIMIT {limit};',
            'id',
            0,
            null,
            -1 // must be > 0
        );
    }

    public function testColumnMustBeFoundInResults(): void
    {
        $this->expectException(LogicException::class);

        $this->createTable('user', [
            'id' => Types::INTEGER,
            'name' => Types::STRING,
        ]);
        $this->connection->insert('user', ['id' => 1, 'name' => 'Anthony Curtis']);
        $this->connection->insert('user', ['id' => 2, 'name' => 'Paige Pritchard']);
        $this->connection->insert('user', ['id' => 3, 'name' => 'Georgia Tyler']);

        $reader = new DoctrineDBALQueryCursorReader(
            $this->doctrine,
            // $sql query must include $column in results
            'SELECT name FROM user WHERE id > {after} LIMIT {limit};',
            'id',
            0,
        );

        \iterator_to_array($reader->read(), false);
    }
}
