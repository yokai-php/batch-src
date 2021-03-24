<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Messenger;

final class LaunchJobMessage
{
    /**
     * @var string
     */
    private string $jobName;

    /**
     * @var array
     */
    private array $configuration;

    public function __construct(string $jobName, array $configuration = [])
    {
        $this->jobName = $jobName;
        $this->configuration = $configuration;
    }

    public function getJobName(): string
    {
        return $this->jobName;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
