<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Documentation;

use SplFileInfo;

/**
 * A markdown link in a markdown documentation file.
 */
final class DocLink
{
    public function __construct(
        public string $label,
        public string $package,
        public string $uri,
        public SplFileInfo $pointsToFile,
        public string $branch,
        public bool $absolute,
    ) {
    }
}
