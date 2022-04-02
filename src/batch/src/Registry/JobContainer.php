<?php

declare(strict_types=1);

namespace Yokai\Batch\Registry;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yokai\Batch\Job\JobInterface;

/**
 * This {@see ContainerInterface} implementation
 * suits for providing a static implementation to {@see JobRegistry}.
 */
final class JobContainer implements ContainerInterface
{
    /**
     * @var array<string, JobInterface>
     */
    private array $jobs;

    /**
     * @param array<string, JobInterface> $jobs
     */
    public function __construct(array $jobs)
    {
        $this->jobs = $jobs;
    }

    public function get(string $id): JobInterface
    {
        if (!isset($this->jobs[$id])) {
            $message = \sprintf('You have requested a non-existent job "%s".', $id);
            throw new class ($message) extends Exception implements NotFoundExceptionInterface {
            };
        }

        return $this->jobs[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->jobs[$id]);
    }
}
