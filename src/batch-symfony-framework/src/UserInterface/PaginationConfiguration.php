<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\UserInterface;

/**
 * Holds UI pagination settings configured via the bundle.
 */
final readonly class PaginationConfiguration
{
    public function __construct(
        public int $pageSize,
        public int $pageRange,
    ) {
    }
}
