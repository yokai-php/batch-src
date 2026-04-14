<?php

declare(strict_types=1);

use Doctrine\Persistence\ConnectionRegistry;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALJobExecutionStorage;
use Yokai\Batch\Factory\JobExecutionLoggerFactory\InMemoryJobExecutionLoggerFactory;

/** @var ConnectionRegistry $connectionRegistry */

new DoctrineDBALJobExecutionStorage($connectionRegistry, new InMemoryJobExecutionLoggerFactory(), [
    'connection' => null, // will use default one, but you can pick any registered connection name
    'table' => 'yokai_batch_job_execution', // change table name if you need
]);
