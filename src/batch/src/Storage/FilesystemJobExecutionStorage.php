<?php

declare(strict_types=1);

namespace Yokai\Batch\Storage;

use Generator;
use Throwable;
use Yokai\Batch\Exception\CannotRemoveJobExecutionException;
use Yokai\Batch\Exception\CannotStoreJobExecutionException;
use Yokai\Batch\Exception\FilesystemException;
use Yokai\Batch\Exception\JobExecutionNotFoundException;
use Yokai\Batch\JobExecution;
use Yokai\Batch\JobExecutionResultFile;
use Yokai\Batch\Serializer\JobExecutionSerializerInterface;

/**
 * This {@see JobExecutionStorageInterface} do persist {@see JobExecution} on a filesystem.
 * Every {@see JobExecution} will be stored on an individual file,
 * in a dir named with the job name : /path/to/dir/{job name}/{execution id}.{extension}.
 *
 * Example:
 *
 *     /path/to/dir/
 *     ├── import/
 *     │   └── 61519f8e0e868.json
 *     │   └── 61519f8e465a6.json
 *     ├── export/
 *     │   └── 61519f8e0f4a7.json
 *     │   └── 61519f8e46fb3.json
 */
final class FilesystemJobExecutionStorage implements QueryableJobExecutionStorageInterface
{
    public function __construct(
        private readonly JobExecutionSerializerInterface $serializer,
        private readonly JobExecutionSerializerInterface $partialSerializer,
        private readonly string $directory,
    ) {
    }

    public function store(JobExecution $execution): void
    {
        try {
            $this->executionToFile($execution);
        } catch (Throwable $exception) {
            throw new CannotStoreJobExecutionException($execution->getJobName(), $execution->getId(), $exception);
        }
    }

    public function remove(JobExecution $execution): void
    {
        try {
            $path = $this->buildFilePath($execution->getJobName(), $execution->getId());
            if (!\file_exists($path)) {
                throw FilesystemException::fileNotFound($path);
            }
            if (!@\unlink($path)) {
                throw FilesystemException::cannotRemoveFile($path);
            }
        } catch (Throwable $exception) {
            throw new CannotRemoveJobExecutionException($execution->getJobName(), $execution->getId(), $exception);
        }
    }

    public function retrieve(string $jobName, string $executionId): JobExecution
    {
        try {
            $path = $this->buildFilePath($jobName, $executionId);

            return $this->fileToExecution($path);
        } catch (Throwable $exception) {
            throw new JobExecutionNotFoundException($jobName, $executionId, $exception);
        }
    }

    public function list(string $jobName): iterable
    {
        $glob = new \GlobIterator($this->buildFilePath($jobName, '*'));
        /** @var \SplFileInfo $file */
        foreach ($glob as $file) {
            try {
                yield $this->fileToExecution($file->getPathname());
            } catch (Throwable) {
                // todo should we do something
            }
        }
    }

    /**
     * @return Generator<JobExecution>
     */
    public function query(Query $query): iterable
    {
        $jobExecutionResultFiles = $this->getResultFiles($query);

        $offset = $query->offset();
        $max = $offset + $query->limit();
        foreach ($jobExecutionResultFiles as $i => $jobExecutionResultFile) {
            if ($i < $offset) {
                continue;
            }

            if ($i >= $max) {
                break;
            }

            try {
                yield $this->fileToExecution($jobExecutionResultFile->path);
            } catch (Throwable) {
                continue;
            }
        }
    }

    public function count(Query $query): int
    {
        $total = \count($this->getResultFiles($query));

        $offset = $query->offset();
        if ($offset >= $total) {
            return 0;
        }

        $remaining = $total - $offset;

        return \min($remaining, $query->limit());
    }

    private function buildFilePath(string $jobName, string $executionId): string
    {
        return \implode(DIRECTORY_SEPARATOR, [$this->directory, $jobName, $executionId]) .
            '.' . $this->serializer->extension();
    }

    private function executionToFile(JobExecution $execution): void
    {
        $path = $this->buildFilePath($execution->getJobName(), $execution->getId());
        $dir = \dirname($path);
        if (!\is_dir($dir) && @\mkdir($dir, 0777, true) === false) {
            throw FilesystemException::cannotCreateDir($path);
        }

        $content = $this->serializer->serialize($execution);

        if (@\file_put_contents($path, $content) === false) {
            throw FilesystemException::cannotWriteFile($path);
        }
    }

    private function fileToExecution(string $file): JobExecution
    {
        $content = @\file_get_contents($file);
        if ($content === false) {
            throw FilesystemException::cannotReadFile($file);
        }

        return $this->serializer->unserialize($content);
    }

    private function fileToPartialExecution(string $file): JobExecution
    {
        $handle = \fopen($file, 'rb+');
        if ($handle === false) {
            throw FilesystemException::cannotReadFile($file);
        }

        try {
            // Read only the first bytes to avoid memory leaking by reading the whole file
            $content = \fread($handle, 2048);
            if ($content === false) {
                throw FilesystemException::cannotReadFile($file);
            }
        } finally {
            \fclose($handle);
        }

        return $this->partialSerializer->unserialize($content);
    }

    /**
     * @return list<JobExecutionResultFile>
     */
    private function getResultFiles(Query $query): array
    {
        // As the values below will be constant through the loop, we extract and compute them once

        $queryNames = $query->jobs();
        $hasQueryNames = \count($queryNames) > 0;

        $queryIds = $query->ids();
        $hasQueryIds = \count($queryIds) > 0;

        $queryStatuses = $query->statuses();
        $hasQueryStatuses = \count($queryStatuses) > 0;

        $queryStartDateFrom = $query->startTime()?->getFrom();
        $hasQueryStartDateFrom = $queryStartDateFrom !== null;

        $queryStartDateTo = $query->startTime()?->getTo();
        $hasQueryStartDateTo = $queryStartDateTo !== null;

        $queryEndDateFrom = $query->endTime()?->getFrom();
        $hasQueryEndDateFrom = $queryEndDateFrom !== null;

        $queryEndDateTo = $query->endTime()?->getTo();
        $hasQueryEndDateTo = $queryEndDateTo !== null;

        // To avoid OOM, we first filter files by reading only metadata from each file,
        // then we sort and paginate the resulting list of files,
        // and finally we read the content of the selected files only to yield JobExecution instances.

        /** @var list<JobExecutionResultFile> $jobExecutionResultFiles */
        $jobExecutionResultFiles = [];

        $glob = new \GlobIterator($this->buildFilePath('**', '*'));

        /** @var \SplFileInfo $file */
        foreach ($glob as $file) {
            $filePathName = $file->getPathname();

            try {
                $execution = $this->fileToPartialExecution($filePathName);
            } catch (Throwable $exception) {
                \error_log(
                    \sprintf(
                        'Cannot read job execution result from file "%s": %s',
                        $filePathName,
                        $exception->getMessage(),
                    ),
                );

                continue;
            }

            if ($hasQueryNames && !\in_array($execution->getJobName(), $queryNames, true)) {
                continue;
            }

            if ($hasQueryIds && !\in_array($execution->getId(), $queryIds, true)) {
                continue;
            }

            if ($hasQueryStatuses && !$execution->getStatus()->isOneOf($queryStatuses)) {
                continue;
            }

            $startTime = $execution->getStartTime();
            if ($hasQueryStartDateFrom && ($startTime === null || $startTime < $queryStartDateFrom)) {
                continue;
            }
            if ($hasQueryStartDateTo && ($startTime === null || $startTime > $queryStartDateTo)) {
                continue;
            }

            $endTime = $execution->getEndTime();
            if ($hasQueryEndDateFrom && ($endTime === null || $endTime < $queryEndDateFrom)) {
                continue;
            }
            if ($hasQueryEndDateTo && ($endTime === null || $endTime > $queryEndDateTo)) {
                continue;
            }

            $jobExecutionResultFiles[] = new JobExecutionResultFile($filePathName, $startTime, $endTime);
        }

        $order = match ($query->sort()) {
            Query::SORT_BY_START_ASC => static function (
                JobExecutionResultFile $left,
                JobExecutionResultFile $right,
            ): int {
                return $left->jobStartTime <=> $right->jobStartTime;
            },
            Query::SORT_BY_START_DESC => static function (
                JobExecutionResultFile $left,
                JobExecutionResultFile $right,
            ): int {
                return $right->jobStartTime <=> $left->jobStartTime;
            },
            Query::SORT_BY_END_ASC => static function (
                JobExecutionResultFile $left,
                JobExecutionResultFile $right,
            ): int {
                return $left->jobEndTime <=> $right->jobEndTime;
            },
            Query::SORT_BY_END_DESC => static function (
                JobExecutionResultFile $left,
                JobExecutionResultFile $right,
            ): int {
                return $right->jobEndTime <=> $left->jobEndTime;
            },
            default => null,
        };

        if ($order) {
            \usort($jobExecutionResultFiles, $order);
        }

        return $jobExecutionResultFiles;
    }
}
