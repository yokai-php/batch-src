<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\League\Flysystem;

use Closure;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\FilesystemWriter;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;
use Yokai\Batch\JobExecution;

/**
 * This job allows you to move files from one filesystem ot another.
 */
final class MoveFilesJob implements JobInterface
{
    public function __construct(
        private JobParameterAccessorInterface $location,
        private FilesystemOperator $source,
        private FilesystemWriter $destination,
        private ?Closure $transformLocation = null,
    ) {
    }

    public function execute(JobExecution $jobExecution): void
    {
        $config = $this->location->get($jobExecution);
        if (\is_string($config)) {
            $locations = [$config];
        } elseif (\is_array($config)) {
            $locations = $config;
        } else {
            throw UnexpectedValueException::type('string|string[]', $config);
        }

        $transformLocation = $this->transformLocation ?? fn ($sourceLocation) => $sourceLocation;
        foreach ($locations as $sourceLocation) {
            $destinationLocation = $transformLocation($sourceLocation);
            if (!\is_string($destinationLocation)) {
                throw UnexpectedValueException::type('string', $destinationLocation);
            }

            try {
                $this->destination->writeStream(
                    $destinationLocation,
                    $this->source->readStream($sourceLocation)
                );
                $this->source->delete($sourceLocation);
            } catch (UnableToReadFile $exception) {
                $jobExecution->addFailureException($exception, [], false);
                $jobExecution->getLogger()->error(
                    'Unable to read file from filesystem.',
                    ['file' => $sourceLocation]
                );
                continue;
            } catch (UnableToWriteFile $exception) {
                $jobExecution->addFailureException($exception, [], false);
                $jobExecution->getLogger()->error(
                    'Unable to write file to filesystem.',
                    ['file' => $destinationLocation]
                );
                continue;
            } catch (UnableToDeleteFile $exception) {
                $jobExecution->addFailureException($exception, [], false);
                $jobExecution->getLogger()->error(
                    'Unable to delete file from filesystem.',
                    ['file' => $sourceLocation]
                );
                continue;
            } catch (FilesystemException $exception) {
                $jobExecution->addFailureException($exception, [], false);
                $jobExecution->getLogger()->error(
                    'Unable to move file.',
                    ['source' => $sourceLocation, 'destination' => $destinationLocation]
                );
                continue;
            }

            $jobExecution->getLogger()->notice(
                'Moved file from filesystem to another.',
                ['source' => $sourceLocation, 'destination' => $destinationLocation]
            );
        }
    }
}
