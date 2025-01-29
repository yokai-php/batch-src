<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\League\Flysystem\Writer;

use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Item\ItemWriterInterface;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;
use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;

final class WriteToFileWriter implements ItemWriterInterface, JobExecutionAwareInterface
{
    use JobExecutionAwareTrait;

    public function __construct(
        private FilesystemWriter $filesystem,
        private JobParameterAccessorInterface|null $location = null,
    ) {
    }

    public function write(iterable $items): void
    {
        $location = '';
        if ($this->location !== null) {
            $location = $this->location->get($this->jobExecution);
        }
        if (!\is_string($location)) {
            throw UnexpectedValueException::type('string', $location);
        }

        foreach ($items as $item) {
            $this->filesystem->write($location, $item);
        }
        // TODO: Implement write() method.
    }
}
