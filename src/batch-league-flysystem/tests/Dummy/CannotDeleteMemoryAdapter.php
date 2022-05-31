<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\League\Flysystem\Dummy;

use League\Flysystem\FilesystemException;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UnableToDeleteFile;

final class CannotDeleteMemoryAdapter extends InMemoryFilesystemAdapter
{
    public function __construct(
        private UnableToDeleteFile|FilesystemException $exception,
    ) {
        parent::__construct();
    }

    public function delete(string $path): void
    {
        throw $this->exception;
    }
}
