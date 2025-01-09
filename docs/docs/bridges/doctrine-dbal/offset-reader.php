<?php

declare(strict_types=1);

use Doctrine\Persistence\ConnectionRegistry;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALQueryOffsetReader;

/** @var ConnectionRegistry $connectionRegistry */

new DoctrineDBALQueryOffsetReader(
    doctrine: $connectionRegistry,
    sql: 'select * from user limit {limit} offset {offset};',
    connection: null, // will use default one, but you can pick any registered connection name
    batch: 500, // configure how many rows are fetched every time the query is executed
);
