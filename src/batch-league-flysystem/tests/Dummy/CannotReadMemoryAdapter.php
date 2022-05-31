<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\League\Flysystem\Dummy;

use League\Flysystem\FilesystemException;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UnableToReadFile;

final class CannotReadMemoryAdapter extends InMemoryFilesystemAdapter
{
    public function __construct(
        private UnableToReadFile|FilesystemException $exception,
    ) {
        parent::__construct();
    }

    public function read(string $path): string
    {
        throw $this->exception;
    }

    public function readStream(string $path)
    {
        throw $this->exception;
    }
}
