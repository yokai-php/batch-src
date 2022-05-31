<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\League\Flysystem\Scheduler;

use Closure;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Trigger\Scheduler\ScheduledJob;
use Yokai\Batch\Trigger\Scheduler\SchedulerInterface;

/**
 * Watch for the existence of a location on a filesystem to trigger a job.
 */
final class FileFoundScheduler implements SchedulerInterface
{
    public function __construct(
        private FilesystemReader $filesystem,
        private JobParameterAccessorInterface $location,
        private string $jobName,
        /**
         * @var array<string, mixed>|Closure|null
         */
        private array|Closure|null $parameters = null,
        private string|Closure|null $executionId = null,
    ) {
    }

    public function get(JobExecution $execution): iterable
    {
        $location = $this->location->get($execution);
        if (!\is_string($location)) {
            throw UnexpectedValueException::type('string', $location);
        }

        try {
            if (!$this->filesystem->has($location)) {
                return [];
            }
        } catch (FilesystemException $exception) {
            $execution->addFailureException($exception, [], false);
            $execution->getLogger()->error(
                'Unable to assert that location exists on filesystem.',
                ['file' => $location]
            );

            return [];
        }

        $parameters = [];
        if (\is_array($this->parameters)) {
            $parameters = $this->parameters;
        } elseif (\is_callable($this->parameters)) {
            $parameters = ($this->parameters)($execution);
        }

        $id = null;
        if (\is_string($this->executionId)) {
            $id = $this->executionId;
        } elseif (\is_callable($this->executionId)) {
            $id = ($this->executionId)($execution);
        }

        return [new ScheduledJob($this->jobName, $parameters, $id)];
    }
}
