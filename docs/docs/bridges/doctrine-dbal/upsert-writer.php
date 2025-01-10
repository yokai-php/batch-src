<?php

declare(strict_types=1);

use Doctrine\Persistence\ConnectionRegistry;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALUpsertWriter;

/** @var ConnectionRegistry $connectionRegistry */

new DoctrineDBALUpsertWriter(
    doctrine: $connectionRegistry,
    connection: null, // will use default one, but you can pick any registered connection name
);
