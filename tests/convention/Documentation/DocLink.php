<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Documentation;

use SplFileInfo;

final class DocLink
{
    public function __construct(
        public DocFile $file,
        public string $label,
        public string $package,
        public string $uri,
        public SplFileInfo $pointsToFile,
        public string $branch,
        public bool $absolute,
    ) {
    }
}
