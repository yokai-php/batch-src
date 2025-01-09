<?php

declare(strict_types=1);

use Doctrine\Persistence\ConnectionRegistry;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALUpsert;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALUpsertWriter;
use Yokai\Batch\Job\Item\ItemJob;
use Yokai\Batch\Job\Item\ItemReaderInterface;
use Yokai\Batch\Job\Item\Processor\CallbackProcessor;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/** @var ConnectionRegistry $connectionRegistry */
/** @var ItemReaderInterface $reader */
/** @var JobExecutionStorageInterface $executionStorage */

new ItemJob(
    batchSize: 500,
    reader: $reader,
    processor: new CallbackProcessor(fn (array $data) => new DoctrineDBALUpsert(
        table: 'user',
        data: $data,
        identity: isset($data['id']) ? ['id' => $data['id']] : [],
    )),
    writer: new DoctrineDBALUpsertWriter($connectionRegistry),
    executionStorage: $executionStorage,
);
