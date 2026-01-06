<?php

declare(strict_types=1);

namespace Yokai\Batch\Serializer;

use Exception;
use Throwable;
use Yokai\Batch\Exception\RuntimeException;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * This {@see JobExecutionStorageInterface} will (un)serialise any {@see JobExecution} to/from json,
 * using internal (de)normalisation and PHP {@see json_encode} and {@see json_decode} functions.
 *
 * @phpstan-import-type JobExecutionDefinition from JobExecutionSerializerTrait
 */
final class JsonJobExecutionSerializer implements JobExecutionSerializerInterface
{
    use JobExecutionSerializerTrait;

    public function serialize(JobExecution $jobExecution): string
    {
        try {
            $json = \json_encode($this->toArray($jobExecution));
            if (!\is_string($json) || \json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(\json_last_error_msg());
            }
        } catch (Throwable $exception) {
            throw RuntimeException::error($exception, 'Cannot serialize job execution to JSON.');
        }

        return $json;
    }

    public function unserialize(string $serializedJobExecution): JobExecution
    {
        try {
            $data = \json_decode($serializedJobExecution, true);
            if (\json_last_error() !== \JSON_ERROR_NONE) {
                throw new Exception(\json_last_error_msg());
            }
            if (!\is_array($data)) {
                throw UnexpectedValueException::type('array', $data);
            }

            /** @var JobExecutionDefinition $data */
            return $this->fromArray($data);
        } catch (Throwable $exception) {
            throw RuntimeException::error($exception, 'Cannot unserialize job execution from JSON.');
        }
    }

    public function extension(): string
    {
        return 'json';
    }
}
