<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\League\Flysystem\Dummy;

use League\Flysystem\Config;
use League\Flysystem\FilesystemException;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UnableToWriteFile;

final class CannotWriteMemoryAdapter extends InMemoryFilesystemAdapter
{
    public function __construct(
        private UnableToWriteFile|FilesystemException $exception,
    ) {
        parent::__construct();
    }

    public function write(string $path, string $contents, Config $config): void
    {
        throw $this->exception;
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        throw $this->exception;
    }
}
