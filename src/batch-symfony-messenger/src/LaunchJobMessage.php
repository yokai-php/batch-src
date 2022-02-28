<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Messenger;

/**
 * A message, dispatched in symfony/messenger's bus, that is requiring to launch a job.
 */
final class LaunchJobMessage
{
    public function __construct(
        private string $jobName,
        /**
         * @phpstan-var array<string, mixed>
         */
        private array $configuration = [],
    ) {
    }

    public function getJobName(): string
    {
        return $this->jobName;
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
