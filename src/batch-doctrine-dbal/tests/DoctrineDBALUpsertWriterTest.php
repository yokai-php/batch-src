<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Types\Types;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALUpsert;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALUpsertWriter;
use Yokai\Batch\JobExecution;

class DoctrineDBALUpsertWriterTest extends DoctrineDBALTestCase
{
    public function test(): void
    {
        $this->createTable('food', [
            'id' => Types::INTEGER,
            'type' => Types::STRING,
            'name' => Types::STRING,
        ]);
        $this->createTable('restaurant', [
            'id' => Types::INTEGER,
            'type' => Types::STRING,
            'name' => Types::STRING,
        ]);

        $writer = new DoctrineDBALUpsertWriter($this->doctrine);
        $writer->setJobExecution($execution = JobExecution::createRoot('123', 'testing'));

        $writer->write([
            new DoctrineDBALUpsert('food', ['id' => 1, 'type' => 'fruit', 'name' => 'Tomatoes']),
            new DoctrineDBALUpsert('food', ['id' => 2, 'type' => 'meet', 'name' => 'Bacon']),
            new DoctrineDBALUpsert('restaurant', ['id' => 1, 'type' => 'French', 'name' => 'Chez Michel']),
            new DoctrineDBALUpsert('restaurant', ['id' => 2, 'type' => 'American', 'name' => 'Guy\'s Diner']),
            new DoctrineDBALUpsert('food', ['name' => 'Tomato'], ['id' => 1]),
        ]);
        self::assertSame([
            ['id' => '1', 'type' => 'fruit', 'name' => 'Tomato'],
            ['id' => '2', 'type' => 'meet', 'name' => 'Bacon'],
        ], $this->findAll('food'));
        self::assertSame([
            ['id' => '1', 'type' => 'French', 'name' => 'Chez Michel'],
            ['id' => '2', 'type' => 'American', 'name' => 'Guy\'s Diner'],
        ], $this->findAll('restaurant'));

        $warnings = $execution->getWarnings();
        self::assertCount(0, $warnings);

        $writer->write([
            new DoctrineDBALUpsert('food', ['id' => 3, 'type' => 'fruit', 'name' => 'Bananas']),
            new DoctrineDBALUpsert('food', ['name' => 'Banana'], ['type' => 'fruit']),
        ]);
        self::assertSame([
            ['id' => '1', 'type' => 'fruit', 'name' => 'Banana'],
            ['id' => '2', 'type' => 'meet', 'name' => 'Bacon'],
            ['id' => '3', 'type' => 'fruit', 'name' => 'Banana'],
        ], $this->findAll('food'));
        self::assertSame([
            ['id' => '1', 'type' => 'French', 'name' => 'Chez Michel'],
            ['id' => '2', 'type' => 'American', 'name' => 'Guy\'s Diner'],
        ], $this->findAll('restaurant')); // same as first (table should not have been updated)

        // because we asked for an update of "food" table "where type = fruit"
        // and because there is 2 records matching this criteria, a warning should have been added
        $warnings = $execution->getWarnings();
        self::assertCount(1, $warnings);
        self::assertSame('Update affected more than one line.', $warnings[0]->getMessage());
        self::assertSame(
            ['table' => 'food', 'identity' => ['type' => 'fruit'], 'count' => 2],
            $warnings[0]->getContext()
        );
    }
}
