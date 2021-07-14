<?php

declare(strict_types=1);

namespace Yokai\Batch\Trigger\Scheduler;

use Generator;

class CallbackScheduler implements SchedulerInterface
{
    /**
     * @phpstan-var list<array{0: callable, 1: string, 2: array<string, mixed>, 3: string|null}>
     */
    private array $config;

    /**
     * @phpstan-param list<array{0: callable, 1: string, 2: array<string, mixed>, 3: string|null}> $config
     */
    public function __construct(array $config)
    {
        $this->config = [];
        foreach ($config as $entry) {
            $this->config[] = [
                $entry[0],
                $entry[1],
                $entry[2] ?? [],
                $entry[3] ?? null,
            ];
        }
    }

    public function get(): Generator
    {
        /** @var callable $callback */
        /** @var string $job */
        /** @var array $parameters */
        /** @var string|null $id */
        foreach ($this->config as [$callback, $job, $parameters, $id]) {
            if ($callback()) {
                yield new ScheduledJob($job, $parameters, $id);
            }
        }
    }
}
