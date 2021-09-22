<?php

declare(strict_types=1);

namespace Yokai\Batch\Job\Item\Writer;

use Yokai\Batch\Job\Item\ItemWriterInterface;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;
use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Summary;

/**
 * An {@see ItemWriterInterface} that writes all item to the {@see JobExecution}'s {@see Summary}.
 */
final class SummaryWriter implements ItemWriterInterface, JobExecutionAwareInterface
{
    use JobExecutionAwareTrait;

    private JobParameterAccessorInterface $index;

    public function __construct(JobParameterAccessorInterface $index)
    {
        $this->index = $index;
    }

    /**
     * @inheritdoc
     */
    public function write(iterable $items): void
    {
        $key = $this->index->get($this->jobExecution);
        foreach ($items as $item) {
            $this->jobExecution->getSummary()->append($key, $item);
        }
    }
}