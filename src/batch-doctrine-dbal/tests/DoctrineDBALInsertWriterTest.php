<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Schema\Exception\TableDoesNotExist;
use Doctrine\DBAL\Types\Types;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALInsertWriter;
use Yokai\Batch\Exception\UnexpectedValueException;

final class DoctrineDBALInsertWriterTest extends DoctrineDBALTestCase
{
    public function test(): void
    {
        $this->createTable('persons', [
            'firstName' => Types::STRING,
            'lastName' => Types::STRING,
        ]);

        $writer = new DoctrineDBALInsertWriter($this->doctrine, 'persons');

        $writer->write([
            ['firstName' => 'John', 'lastName' => 'Doe'],
            ['firstName' => 'Jane', 'lastName' => 'Doe'],
        ]);
        self::assertSame([
            ['firstName' => 'John', 'lastName' => 'Doe'],
            ['firstName' => 'Jane', 'lastName' => 'Doe'],
        ], $this->findAll('persons'));

        $writer->write([
            ['firstName' => 'Jack', 'lastName' => 'Doe'],
        ]);
        self::assertSame([
            ['firstName' => 'John', 'lastName' => 'Doe'],
            ['firstName' => 'Jane', 'lastName' => 'Doe'],
            ['firstName' => 'Jack', 'lastName' => 'Doe'],
        ], $this->findAll('persons'));
    }

    public function testAutoDetectsColumnTypes(): void
    {
        $this->createTable('persons', [
            'id' => Types::INTEGER,
            'firstName' => Types::STRING,
        ]);

        $writer = new DoctrineDBALInsertWriter($this->doctrine, 'persons');

        $writer->write([
            ['id' => 1, 'firstName' => 'John'],
            ['id' => 2, 'firstName' => 'Jane'],
        ]);

        self::assertSame([
            ['id' => '1', 'firstName' => 'John'],
            ['id' => '2', 'firstName' => 'Jane'],
        ], $this->findAll('persons'));
    }

    public function testWithExplicitTypes(): void
    {
        $this->createTable('events', [
            'name' => Types::STRING,
            'occurred_at' => Types::DATETIME_IMMUTABLE,
            'active' => Types::BOOLEAN,
        ]);

        $writer = new DoctrineDBALInsertWriter(
            $this->doctrine,
            'events',
            types: [
                'occurred_at' => Types::DATETIME_IMMUTABLE,
                'active' => Types::BOOLEAN,
            ],
        );

        $writer->write([
            ['name' => 'signup', 'occurred_at' => new \DateTimeImmutable('2024-01-15 10:00:00'), 'active' => true],
            ['name' => 'logout', 'occurred_at' => new \DateTimeImmutable('2024-01-16 08:30:00'), 'active' => false],
        ]);

        self::assertSame([
            ['name' => 'signup', 'occurred_at' => '2024-01-15 10:00:00', 'active' => '1'],
            ['name' => 'logout', 'occurred_at' => '2024-01-16 08:30:00', 'active' => '0'],
        ], $this->findAll('events'));
    }

    public function testItemNotAnArray(): void
    {
        $this->createTable('persons', [
            'firstName' => Types::STRING,
            'lastName' => Types::STRING,
        ]);
        $this->expectException(UnexpectedValueException::class);
        $writer = new DoctrineDBALInsertWriter($this->doctrine, 'persons');
        $writer->write(['string']);
    }

    public function testTableDoesNotExists(): void
    {
        $this->expectException(TableDoesNotExist::class);
        $writer = new DoctrineDBALInsertWriter($this->doctrine, 'persons');
        $writer->write([
            ['firstName' => 'John', 'lastName' => 'Doe'],
            ['firstName' => 'Jane', 'lastName' => 'Doe'],
        ]);
    }
}
