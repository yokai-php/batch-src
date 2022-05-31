<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\League\Flysystem\Dummy;

use League\Flysystem\FilesystemException;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UnableToCheckExistence;

final class CannotCheckMemoryAdapter extends InMemoryFilesystemAdapter
{
    public function __construct(
        private UnableToCheckExistence|FilesystemException $exception,
    ) {
        parent::__construct();
    }

    public function fileExists(string $path): bool
    {
        throw $this->exception;
    }

    public function directoryExists(string $path): bool
    {
        throw $this->exception;
    }
}
