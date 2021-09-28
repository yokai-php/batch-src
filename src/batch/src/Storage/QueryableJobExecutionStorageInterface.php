<?php

declare(strict_types=1);

namespace Yokai\Batch\Storage;

use Yokai\Batch\JobExecution;

/**
 * Fetch a list of all {@see JobExecution}, matching a query.
 */
interface QueryableJobExecutionStorageInterface extends ListableJobExecutionStorageInterface
{
    /**
     * @return iterable|JobExecution[]
     */
    public function query(Query $query): iterable;
}
