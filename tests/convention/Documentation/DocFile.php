<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Documentation;

use SplFileInfo;

/**
 * A Sphinx documentation file.
 */
final class DocFile
{
    public function __construct(
        public SplFileInfo $file,
        /**
         * @var array<DocLink>
         */
        public array $links,
    ) {
    }
}
