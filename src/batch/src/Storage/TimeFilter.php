<?php

declare(strict_types=1);

namespace Yokai\Batch\Storage;

use DateTimeInterface;

/**
 * DTO with optional time boundaries.
 */
final class TimeFilter
{
    public function __construct(
        private ?DateTimeInterface $from,
        private ?DateTimeInterface $to,
    ) {
    }

    public function getFrom(): ?DateTimeInterface
    {
        return $this->from;
    }

    public function getTo(): ?DateTimeInterface
    {
        return $this->to;
    }
}
