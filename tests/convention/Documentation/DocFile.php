<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Documentation;

use SplFileInfo;

final class DocFile
{
    public function __construct(
        public string $package,
        public SplFileInfo $file,
    ) {
    }
}
