<?php

declare(strict_types=1);

namespace Yokai\Batch\Trigger\Scheduler;

use DateTimeImmutable;
use DateTimeInterface;

final class TimeScheduler extends CallbackScheduler
{
    /**
     * @phpstan-param list<array{0: DateTimeInterface, 1: string, 2: array<string, mixed>, 3: string|null}> $config
     */
    public function __construct(array $config)
    {
        $parentConfig = [];
        foreach ($config as $entry) {
            $parentConfig[] = [
                fn () => $entry[0] <= new DateTimeImmutable(),
                $entry[1],
                $entry[2] ?? [],
                $entry[3] ?? null,
            ];
        }
        parent::__construct($parentConfig);
    }
}
