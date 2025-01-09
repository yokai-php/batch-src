<?php

declare(strict_types=1);

use Doctrine\Persistence\ConnectionRegistry;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALQueryCursorReader;

/** @var ConnectionRegistry $connectionRegistry */

new DoctrineDBALQueryCursorReader(
    doctrine: $connectionRegistry,
    sql: 'select * from user where id > {after} order by id asc limit {limit};',
    column: 'id', // the column that is used in both where and sort clauses
    start: 0, // the very first value to use, generally 0 or a very old date
    connection: null, // will use default one, but you can pick any registered connection name
    batch: 500, // configure how many rows are fetched every time the query is executed
);
