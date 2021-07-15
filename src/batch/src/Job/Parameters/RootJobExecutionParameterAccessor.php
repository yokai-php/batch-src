<?php

declare(strict_types=1);

namespace Yokai\Batch\Job\Parameters;

use Yokai\Batch\Exception\CannotAccessParameterException;
use Yokai\Batch\Exception\UndefinedJobParameterException;
use Yokai\Batch\JobExecution;

/**
 * This job parameter accessor implementation access parameters
 * through the related root execution parameters of the contextual JobExecution.
 */
final class RootJobExecutionParameterAccessor implements JobParameterAccessorInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public function get(JobExecution $execution)
    {
        try {
            return $execution->getRootExecution()->getParameter($this->name);
        } catch (UndefinedJobParameterException $exception) {
            throw new CannotAccessParameterException(
                \sprintf('Cannot access "%s" parameter from root job execution.', $this->name),
                $exception
            );
        }
    }
}
