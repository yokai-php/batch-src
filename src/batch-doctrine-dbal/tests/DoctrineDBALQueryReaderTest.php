<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Types\Types;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALQueryReader;
use Yokai\Batch\Exception\InvalidArgumentException;

class DoctrineDBALQueryReaderTest extends DoctrineDBALTestCase
{
    public function test(): void
    {
        $this->createTable('numbers', [
            'as_int' => Types::INTEGER,
            'as_string' => Types::STRING,
        ]);
        $this->connection->insert('numbers', ['as_int' => 0, 'as_string' => 'Zero']);
        $this->connection->insert('numbers', ['as_int' => 1, 'as_string' => 'One']);
        $this->connection->insert('numbers', ['as_int' => 2, 'as_string' => 'Two']);
        $this->connection->insert('numbers', ['as_int' => 3, 'as_string' => 'Three']);
        $this->connection->insert('numbers', ['as_int' => 4, 'as_string' => 'Four']);
        $this->connection->insert('numbers', ['as_int' => 5, 'as_string' => 'Five']);
        $this->connection->insert('numbers', ['as_int' => 6, 'as_string' => 'Six']);
        $this->connection->insert('numbers', ['as_int' => 7, 'as_string' => 'Seven']);
        $this->connection->insert('numbers', ['as_int' => 8, 'as_string' => 'Height']);
        $this->connection->insert('numbers', ['as_int' => 9, 'as_string' => 'Nine']);

        $reader = new DoctrineDBALQueryReader(
            $this->doctrine,
            'SELECT * FROM numbers LIMIT {limit} OFFSET {offset};',
            null,
            4 // we will take 4 records at once : 3 queries expected
        );

        self::assertSame([
            ['as_int' => '0', 'as_string' => 'Zero'],
            ['as_int' => '1', 'as_string' => 'One'],
            ['as_int' => '2', 'as_string' => 'Two'],
            ['as_int' => '3', 'as_string' => 'Three'],
            ['as_int' => '4', 'as_string' => 'Four'],
            ['as_int' => '5', 'as_string' => 'Five'],
            ['as_int' => '6', 'as_string' => 'Six'],
            ['as_int' => '7', 'as_string' => 'Seven'],
            ['as_int' => '8', 'as_string' => 'Height'],
            ['as_int' => '9', 'as_string' => 'Nine'],
        ], \iterator_to_array($reader->read(), false));
    }

    public function testQueryMustContainsLimitPlaceholder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DoctrineDBALQueryReader($this->doctrine, 'SELECT * FROM some table LIMIT 1 OFFSET {offset};');
    }

    public function testQueryMustContainsOffsetPlaceholder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DoctrineDBALQueryReader($this->doctrine, 'SELECT * FROM some table LIMIT {limit} OFFSET 0;');
    }

    public function testBatchMustBePositiveInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DoctrineDBALQueryReader(
            $this->doctrine,
            'SELECT * FROM some table LIMIT {limit} OFFSET {offset};',
            null,
            0 // must be > 0
        );
    }
}
