<?php

declare(strict_types=1);

namespace Yokai\Batch\Trigger\Scheduler;

final class ScheduledJob
{
    private string $jobName;
    private array $parameters;
    private ?string $id;

    public function __construct(string $jobName, array $parameters = [], string $id = null)
    {
        $this->jobName = $jobName;
        $this->parameters = $parameters;
        $this->id = $id;
    }

    public function getJobName(): string
    {
        return $this->jobName;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
