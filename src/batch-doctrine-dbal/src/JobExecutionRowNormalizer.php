<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\DBAL;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Failure;
use Yokai\Batch\JobExecution;
use Yokai\Batch\JobExecutionLogs;
use Yokai\Batch\JobParameters;
use Yokai\Batch\Summary;
use Yokai\Batch\Warning;

/**
 * @internal
 */
final class JobExecutionRowNormalizer
{
    public function __construct(
        private AbstractPlatform $platform,
    ) {
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    public function toRow(JobExecution $jobExecution): array
    {
        return [
            'id' => $jobExecution->getId(),
            'job_name' => $jobExecution->getJobName(),
            'status' => $jobExecution->getStatus()->getValue(),
            'parameters' => iterator_to_array($jobExecution->getParameters()),
            'start_time' => $jobExecution->getStartTime(),
            'end_time' => $jobExecution->getEndTime(),
            'summary' => $jobExecution->getSummary()->all(),
            'failures' => array_map([$this, 'failureToArray'], $jobExecution->getFailures()),
            'warnings' => array_map([$this, 'warningToArray'], $jobExecution->getWarnings()),
            'child_executions' => array_map([$this, 'toChildRow'], $jobExecution->getChildExecutions()),
            'logs' => $jobExecution->getParentExecution() === null ? (string)$jobExecution->getLogs() : null,
        ];
    }

    /**
     * @phpstan-param array<string, mixed> $data
     */
    public function fromRow(array $data, JobExecution $parent = null): JobExecution
    {
        $data['status'] = intval($data['status']);
        $data['parameters'] = $this->jsonFromString($data['parameters']);
        $data['summary'] = $this->jsonFromString($data['summary']);
        $data['failures'] = $this->jsonFromString($data['failures']);
        $data['warnings'] = $this->jsonFromString($data['warnings']);
        $data['child_executions'] = $this->jsonFromString($data['child_executions']);

        $name = $data['job_name'];
        $status = new BatchStatus(intval($data['status']));
        $parameters = new JobParameters($data['parameters']);
        $summary = new Summary($data['summary']);

        if ($parent !== null) {
            $jobExecution = JobExecution::createChild($parent, $name, $status, $parameters, $summary);
            $parent->addChildExecution($jobExecution);
        } else {
            $jobExecution = JobExecution::createRoot(
                $data['id'],
                $name,
                $status,
                $parameters,
                $summary,
                new JobExecutionLogs($data['logs'])
            );
        }

        $jobExecution->setStartTime($this->dateFromString($data['start_time']));
        $jobExecution->setEndTime($this->dateFromString($data['end_time']));

        foreach ($data['failures'] as $failureData) {
            $jobExecution->addFailure($this->failureFromArray($failureData), false);
        }
        foreach ($data['warnings'] as $warningData) {
            $jobExecution->addWarning($this->warningFromArray($warningData), false);
        }

        foreach ($data['child_executions'] as $childExecutionData) {
            $jobExecution->addChildExecution($this->fromRow($childExecutionData, $jobExecution));
        }

        return $jobExecution;
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    public function toChildRow(JobExecution $jobExecution): array
    {
        return [
            'job_name' => $jobExecution->getJobName(),
            'status' => $jobExecution->getStatus()->getValue(),
            'parameters' => iterator_to_array($jobExecution->getParameters()),
            'start_time' => $this->toDateString($jobExecution->getStartTime()),
            'end_time' => $this->toDateString($jobExecution->getEndTime()),
            'summary' => $jobExecution->getSummary()->all(),
            'failures' => array_map([$this, 'failureToArray'], $jobExecution->getFailures()),
            'warnings' => array_map([$this, 'warningToArray'], $jobExecution->getWarnings()),
            'child_executions' => array_map([$this, 'toChildRow'], $jobExecution->getChildExecutions()),
        ];
    }

    /**
     * @phpstan-param array<int|string, mixed>|string $value
     *
     * @phpstan-return array<int|string, mixed>
     */
    private function jsonFromString(array|string $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        if (!is_array($value)) {
            throw UnexpectedValueException::type('array', $value);
        }

        return $value;
    }

    private function dateFromString(?string $date): ?DateTimeImmutable
    {
        if ($date === null) {
            return null;
        }

        return DateTimeImmutable::createFromFormat($this->platform->getDateTimeFormatString(), $date) ?: null;
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    private function failureToArray(Failure $failure): array
    {
        return [
            'class' => $failure->getClass(),
            'message' => $failure->getMessage(),
            'code' => $failure->getCode(),
            'parameters' => $failure->getParameters(),
            'trace' => $failure->getTrace(),
        ];
    }

    /**
     * @phpstan-param array<string, mixed> $array
     */
    private function failureFromArray(array $array): Failure
    {
        return new Failure(
            $array['class'],
            $array['message'],
            $array['code'],
            $array['parameters'],
            $array['trace']
        );
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    private function warningToArray(Warning $warning): array
    {
        return [
            'message' => $warning->getMessage(),
            'parameters' => $warning->getParameters(),
            'context' => $warning->getContext(),
        ];
    }

    /**
     * @phpstan-param array<string, mixed> $array
     */
    private function warningFromArray(array $array): Warning
    {
        return new Warning($array['message'], $array['parameters'], $array['context']);
    }

    private function toDateString(?DateTimeInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date->format($this->platform->getDateTimeFormatString());
    }
}
