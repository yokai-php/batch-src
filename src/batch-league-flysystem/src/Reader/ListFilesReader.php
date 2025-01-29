<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\League\Flysystem\Reader;

use League\Flysystem\FilesystemReader;
use League\Flysystem\StorageAttributes;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Item\ItemReaderInterface;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;
use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;

final class ListFilesReader implements ItemReaderInterface, JobExecutionAwareInterface
{
    use JobExecutionAwareTrait;

    public function __construct(
        private FilesystemReader $filesystem,
        private JobParameterAccessorInterface|null $location = null,
        private bool $listDeepFiles = false,
        private \Closure|null $acceptContent = null,
        private \Closure|null $transformContent = null,
    ) {
    }

    public static function acceptFilesOnly(): \Closure
    {
        return fn(StorageAttributes $file) => $file->isFile();
    }

    public static function acceptDirectoriesOnly(): \Closure
    {
        return fn(StorageAttributes $file) => $file->isDir();
    }

    public static function transformContent(): \Closure
    {
        return fn(StorageAttributes $file, FilesystemReader $filesystem) => $filesystem->read($file->path());
    }

    public static function transformPublicUrl(): \Closure
    {
        return fn(StorageAttributes $file, FilesystemReader $filesystem) => $filesystem->publicUrl($file->path());
    }

    public function read(): iterable
    {
        $location = '';
        if ($this->location !== null) {
            $location = $this->location->get($this->jobExecution);
        }
        if (!\is_string($location)) {
            throw UnexpectedValueException::type('string', $location);
        }

        foreach ($this->filesystem->listContents($location, $this->listDeepFiles) as $file) {
            $acceptContent = true;
            if ($this->acceptContent !== null) {
                $acceptContent = ($this->acceptContent)($file);
            }
            if (!$acceptContent) {
                continue;
            }

            if ($this->transformContent !== null) {
                $file = ($this->transformContent)($file);
            }

            yield $file;
        }
    }
}
