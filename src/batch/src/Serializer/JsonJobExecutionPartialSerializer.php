<?php

declare(strict_types=1);

namespace Yokai\Batch\Serializer;

use Throwable;
use Yokai\Batch\Exception\RuntimeException;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * This {@see JobExecutionStorageInterface} will <b>partially</b> (un)serialise any {@see JobExecution} to/from json,
 * using internal (de)normalisation and PHP {@see json_encode} and {@see json_decode} functions.
 *
 * Only the following properties will be (un)serialised to avoid OOM issues with large job executions:
 * - id
 * - jobName
 * - status
 * - startTime
 * - endTime
 */
final class JsonJobExecutionPartialSerializer implements JobExecutionSerializerInterface
{
    use JobExecutionSerializerTrait;

    public function serialize(JobExecution $jobExecution): string
    {
        throw new RuntimeException('Not implemented yet.');
    }

    public function unserialize(string $serializedJobExecution): JobExecution
    {
        try {
            $id = null;
            if (\preg_match('/"id"\s*:\s*"([^"]+)"/', $serializedJobExecution, $m)) {
                $id = $m[1];
            }

            if (!\is_string($id)) {
                throw UnexpectedValueException::type('string', $id);
            }

            $jobName = null;
            if (\preg_match('/"jobName"\s*:\s*"([^"]+)"/', $serializedJobExecution, $m)) {
                $jobName = $m[1];
            }

            if (!\is_string($jobName)) {
                throw UnexpectedValueException::type('string', $jobName);
            }

            $status = null;
            if (\preg_match('/"status"\s*:\s*(\d+)/', $serializedJobExecution, $m)) {
                $status = $m[1];
            }

            if (!\is_numeric($status)) {
                throw UnexpectedValueException::type('int', $status);
            }
            $status = (int)$status;

            $startTime = null;
            if (\preg_match('/"startTime"\s*:\s*"([^"]+)"/', $serializedJobExecution, $m)) {
                $startTime = $m[1];
            }

            $endTime = null;
            if (\preg_match('/"endTime"\s*:\s*"([^"]+)"/', $serializedJobExecution, $m)) {
                $endTime = $m[1];
            }
        } catch (Throwable $exception) {
            throw RuntimeException::error($exception, 'Cannot unserialize job execution from JSON.');
        }

        return $this->fromArray([
            'id' => $id,
            'jobName' => $jobName,
            'status' => $status,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'parameters' => [],
            'summary' => [],
            'failures' => [],
            'warnings' => [],
            'childExecutions' => [],
        ]);
    }

    public function extension(): string
    {
        return 'json';
    }
}
