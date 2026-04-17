<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\DBAL;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Failure;
use Yokai\Batch\Factory\JobExecutionLoggerFactoryInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\JobParameters;
use Yokai\Batch\Summary;
use Yokai\Batch\Warning;

/**
 * @internal
 *
 * @phpstan-type FailureData array{
 *     class: string,
 *     message: string,
 *     code: int,
 *     parameters: array<string, string>,
 *     trace: string|null,
 * }
 * @phpstan-type WarningData array{
 *     message: string,
 *     parameters: array<string, string>,
 *     context: array<string, mixed>,
 * }
 * @phpstan-type RowData array{
 *     id: string,
 *     job_name: string,
 *     status: int|string,
 *     parameters: array<string, mixed>|string,
 *     start_time: string|null,
 *     end_time: string|null,
 *     launched_at: string|null,
 *     summary: array<string, mixed>|string,
 *     failures: array<string, mixed>|string,
 *     warnings: array<string, mixed>|string,
 *     child_executions: array<string, mixed>|string,
 *     logs: string|null,
 * }
 */
final readonly class JobExecutionRowNormalizer
{
    public function __construct(
        private AbstractPlatform $platform,
        private JobExecutionLoggerFactoryInterface $loggerFactory,
    ) {
    }

    /**
     * Convert a {@see JobExecution} object to a row data array.
     *
     * @return array<string, mixed>
     */
    public function toRow(JobExecution $jobExecution): array
    {
        return [
            'id' => $jobExecution->getId(),
            'job_name' => $jobExecution->getJobName(),
            'status' => $jobExecution->getStatus()->value,
            'parameters' => \iterator_to_array($jobExecution->getParameters()),
            'start_time' => $jobExecution->getStartTime(),
            'end_time' => $jobExecution->getEndTime(),
            'launched_at' => $jobExecution->getLaunchedAt(),
            'summary' => $jobExecution->getSummary()->all(),
            'failures' => \array_map([$this, 'failureToArray'], $jobExecution->getFailures()),
            'warnings' => \array_map([$this, 'warningToArray'], $jobExecution->getWarnings()),
            'child_executions' => \array_map([$this, 'toChildRow'], $jobExecution->getChildExecutions()),
            'logs' => $jobExecution->getParentExecution() === null ? $jobExecution->getLogger()->getReference() : null,
        ];
    }

    /**
     * Convert a row data array to a {@see JobExecution} object.
     *
     * @param RowData $data
     */
    public function fromRow(array $data, JobExecution|null $parent = null): JobExecution
    {
        $name = $data['job_name'];
        $status = BatchStatus::from((int)$data['status']);
        $parameters = new JobParameters($this->jsonFromString($data['parameters']));
        $summary = new Summary($this->jsonFromString($data['summary']));

        /** @var list<FailureData> $failures */
        $failures = $this->jsonFromString($data['failures']);
        /** @var list<WarningData> $warnings */
        $warnings = $this->jsonFromString($data['warnings']);
        /** @var list<RowData> $childExecutions */
        $childExecutions = $this->jsonFromString($data['child_executions']);

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
                $this->loggerFactory->restore($data['logs'] ?? ''),
            );
        }

        $jobExecution->setStartTime($this->dateFromString($data['start_time']));
        $jobExecution->setEndTime($this->dateFromString($data['end_time']));
        if ($parent === null) {
            $jobExecution->setLaunchedAt($this->dateFromString($data['launched_at'] ?? null));
        }

        foreach ($failures as $failureData) {
            $jobExecution->addFailure($this->failureFromArray($failureData), false);
        }
        foreach ($warnings as $warningData) {
            $jobExecution->addWarning($this->warningFromArray($warningData), false);
        }

        foreach ($childExecutions as $childExecutionData) {
            $jobExecution->addChildExecution($this->fromRow($childExecutionData, $jobExecution));
        }

        return $jobExecution;
    }

    /**
     * @return array<string, mixed>
     */
    private function toChildRow(JobExecution $jobExecution): array
    {
        return [
            'job_name' => $jobExecution->getJobName(),
            'status' => $jobExecution->getStatus()->value,
            'parameters' => \iterator_to_array($jobExecution->getParameters()),
            'start_time' => $this->toDateString($jobExecution->getStartTime()),
            'end_time' => $this->toDateString($jobExecution->getEndTime()),
            'summary' => $jobExecution->getSummary()->all(),
            'failures' => \array_map([$this, 'failureToArray'], $jobExecution->getFailures()),
            'warnings' => \array_map([$this, 'warningToArray'], $jobExecution->getWarnings()),
            'child_executions' => \array_map([$this, 'toChildRow'], $jobExecution->getChildExecutions()),
        ];
    }

    /**
     * @param array<int|string, mixed>|string $value
     *
     * @return array<string, mixed>
     */
    private function jsonFromString(array|string $value): array
    {
        if (\is_string($value)) {
            $value = \json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        if (!\is_array($value)) {
            throw UnexpectedValueException::type('array', $value);
        }

        return $value;
    }

    private function dateFromString(null|string $date): null|DateTimeImmutable
    {
        if ($date === null) {
            return null;
        }

        return DateTimeImmutable::createFromFormat($this->platform->getDateTimeFormatString(), $date) ?: null;
    }

    /**
     * @return FailureData
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
     * @param FailureData $array
     */
    private function failureFromArray(array $array): Failure
    {
        return new Failure(
            $array['class'],
            $array['message'],
            $array['code'],
            $array['parameters'],
            $array['trace'],
        );
    }

    /**
     * @return WarningData
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
     * @param WarningData $array
     */
    private function warningFromArray(array $array): Warning
    {
        return new Warning($array['message'], $array['parameters'], $array['context']);
    }

    private function toDateString(null|DateTimeInterface $date): null|string
    {
        if ($date === null) {
            return null;
        }

        return $date->format($this->platform->getDateTimeFormatString());
    }
}
