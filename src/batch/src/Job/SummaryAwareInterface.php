<?php

declare(strict_types=1);

namespace Yokai\Batch\Job;

use Yokai\Batch\Summary;

/**
 * A class implementing this interface will gain access
 * to {@see Summary} of the current {@see JobExecution}.
 *
 * Summary can also be accessed by implementing {@see JobExecutionAwareInterface}
 * and calling {@see JobExecution::getSummary} on the provided execution.
 */
interface SummaryAwareInterface
{
    /**
     * @param Summary $summary
     */
    public function setSummary(Summary $summary): void;
}
