<?php

declare(strict_types=1);

use Doctrine\Persistence\ConnectionRegistry;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALInsertWriter;

/** @var ConnectionRegistry $connectionRegistry */

new DoctrineDBALInsertWriter(
    doctrine: $connectionRegistry,
    table: 'user',
    connection: null, // will use default one, but you can pick any registered connection name
);
