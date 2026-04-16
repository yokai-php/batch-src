<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Messenger;

/**
 * Holds the Symfony messenger configuration.
 */
final readonly class MessengerJobsConfiguration
{
    public function __construct(
        /**
         * @var array<string, string>
         */
        private array $routing,
    ) {
    }

    /**
     * Get the configured transport name for a job name.
     * Return null if none was provided.
     */
    public function getTransportNameForJobName(string $jobName): string|null
    {
        return $this->routing[$jobName] ?? null;
    }
}
