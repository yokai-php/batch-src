<?php

declare(strict_types=1);

namespace Yokai\Batch\Registry;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Yokai\Batch\Exception\UndefinedJobException;
use Yokai\Batch\Job\JobInterface;

/**
 * This class is a wrapper around a {@see ContainerInterface},
 * responsible for accessing jobs in a typed maner.
 * It can be registered as a global registry,
 * but it can be also created with a subset of jobs if required.
 */
final class JobRegistry
{
    public function __construct(
        private ContainerInterface $jobs,
    ) {
    }

    /**
     * @param array<string, JobInterface> $jobs
     */
    public static function fromJobArray(array $jobs): JobRegistry
    {
        return new self(new JobContainer($jobs));
    }

    /**
     * @throws UndefinedJobException
     */
    public function get(string $name): JobInterface
    {
        try {
            /** @var JobInterface $job */
            $job = $this->jobs->get($name);
        } catch (ContainerExceptionInterface $exception) {
            throw new UndefinedJobException($name, $exception);
        }

        return $job;
    }
}
