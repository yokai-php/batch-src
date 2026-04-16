<?php

declare(strict_types=1);

use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ConnectionRegistry;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALInsertWriter;

/** @var ConnectionRegistry $connectionRegistry */

// Column types are inferred from the table schema automatically
new DoctrineDBALInsertWriter(
    doctrine: $connectionRegistry,
    table: 'user',
    connection: null, // will use default one, but you can pick any registered connection name
);

// Or provide explicit type hints to override auto-detection
new DoctrineDBALInsertWriter(
    doctrine: $connectionRegistry,
    table: 'user',
    types: ['created_at' => Types::DATETIME_IMMUTABLE, 'active' => Types::BOOLEAN],
);
