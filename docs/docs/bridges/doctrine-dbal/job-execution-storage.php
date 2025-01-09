<?php

declare(strict_types=1);

use Doctrine\Persistence\ConnectionRegistry;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALJobExecutionStorage;

/** @var ConnectionRegistry $connectionRegistry */

new DoctrineDBALJobExecutionStorage($connectionRegistry, [
    'connection' => null, // will use default one, but you can pick any registered connection name
    'table' => 'yokai_batch_job_execution', // change table name if you need
]);
