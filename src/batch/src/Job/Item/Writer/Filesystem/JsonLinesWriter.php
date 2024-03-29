<?php

declare(strict_types=1);

namespace Yokai\Batch\Job\Item\Writer\Filesystem;

use Yokai\Batch\Exception\RuntimeException;
use Yokai\Batch\Job\Item\FlushableInterface;
use Yokai\Batch\Job\Item\InitializableInterface;
use Yokai\Batch\Job\Item\ItemWriterInterface;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;
use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;

/**
 * This {@see ItemWriterInterface} writes each item as a JSON string to a file.
 * @link https://jsonlines.org/
 */
final class JsonLinesWriter implements
    ItemWriterInterface,
    InitializableInterface,
    FlushableInterface,
    JobExecutionAwareInterface
{
    use JobExecutionAwareTrait;

    /**
     * @var resource
     */
    private $file;

    public function __construct(
        private JobParameterAccessorInterface $filePath,
    ) {
    }

    public function initialize(): void
    {
        /** @var string $path */
        $path = $this->filePath->get($this->jobExecution);
        $dir = \dirname($path);
        if (!@\is_dir($dir) && !@\mkdir($dir, 0777, true)) {
            throw new RuntimeException(\sprintf('Cannot create dir "%s".', $dir));
        }

        $file = @\fopen($path, 'w+');
        if ($file === false) {
            throw new RuntimeException(\sprintf('Cannot open %s for writing.', $path));
        }

        $this->file = $file;
    }

    public function write(iterable $items): void
    {
        foreach ($items as $json) {
            if (!\is_string($json)) {
                $json = \json_encode($json, JSON_THROW_ON_ERROR);
            }
            \fwrite($this->file, $json . \PHP_EOL);
        }
    }

    public function flush(): void
    {
        \fclose($this->file);
    }
}
