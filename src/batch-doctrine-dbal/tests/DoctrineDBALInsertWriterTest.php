<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Types\Types;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALInsertWriter;
use Yokai\Batch\Exception\UnexpectedValueException;

class DoctrineDBALInsertWriterTest extends DoctrineDBALTestCase
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

    public function testItemNotAnArray(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $writer = new DoctrineDBALInsertWriter($this->doctrine, 'persons');
        $writer->write(['string']);
    }
}
