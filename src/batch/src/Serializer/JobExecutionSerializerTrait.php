<?php

declare(strict_types=1);

namespace Yokai\Batch\Serializer;

use DateTimeImmutable;
use DateTimeInterface;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Failure;
use Yokai\Batch\JobExecution;
use Yokai\Batch\JobExecutionLogs;
use Yokai\Batch\JobParameters;
use Yokai\Batch\Summary;
use Yokai\Batch\Warning;

/**
 * @phpstan-type JobExecutionDefinition array{
 *      id: string,
 *      jobName: string,
 *      status: int,
 *      parameters: array<string, mixed>,
 *      startTime: string|null,
 *      endTime: string|null,
 *      summary: array<string, mixed>,
 *      failures: list<FailureDefinition>,
 *      warnings: list<WarningDefinition>,
 *      childExecutions: iterable<mixed>,
 *      logs?: string,
 *  }
 *
 * @phpstan-type FailureDefinition array{
 *      class: class-string<\Throwable>,
 *      message: string,
 *      code: int,
 *      parameters: array<string, string>,
 *      trace: string|null
 *  }
 *
 * @phpstan-type WarningDefinition array{
 *      message: string,
 *      parameters: array<string, string>,
 *      context: array<string, mixed>
 *  }
 */
trait JobExecutionSerializerTrait
{
    /**
     * @return JobExecutionDefinition
     */
    private function toArray(JobExecution $jobExecution): array
    {
        return [
            'id' => $jobExecution->getId(),
            'jobName' => $jobExecution->getJobName(),
            'status' => $jobExecution->getStatus()->getValue(),
            'parameters' => \iterator_to_array($jobExecution->getParameters()),
            'startTime' => $this->dateToString($jobExecution->getStartTime()),
            'endTime' => $this->dateToString($jobExecution->getEndTime()),
            'summary' => $jobExecution->getSummary()->all(),
            'failures' => \array_map($this->failureToArray(...), $jobExecution->getFailures()),
            'warnings' => \array_map($this->warningToArray(...), $jobExecution->getWarnings()),
            'childExecutions' => \array_map($this->toArray(...), $jobExecution->getChildExecutions()),
            'logs' => $jobExecution->getParentExecution() === null ? (string)$jobExecution->getLogs() : '',
        ];
    }

    /**
     * @param JobExecutionDefinition $jobExecutionData
     */
    private function fromArray(array $jobExecutionData, JobExecution|null $parentExecution = null): JobExecution
    {
        $name = $jobExecutionData['jobName'];
        $status = new BatchStatus($jobExecutionData['status']);
        $parameters = new JobParameters($jobExecutionData['parameters']);
        $summary = new Summary($jobExecutionData['summary']);

        if ($parentExecution !== null) {
            $jobExecution = JobExecution::createChild($parentExecution, $name, $status, $parameters, $summary);
            $parentExecution->addChildExecution($jobExecution);
        } else {
            $jobExecution = JobExecution::createRoot(
                $jobExecutionData['id'],
                $name,
                $status,
                $parameters,
                $summary,
                new JobExecutionLogs($jobExecutionData['logs'] ?? ''),
            );
        }

        $jobExecution->setStartTime($this->stringToDate($jobExecutionData['startTime']));
        $jobExecution->setEndTime($this->stringToDate($jobExecutionData['endTime']));

        foreach ($jobExecutionData['failures'] as $failureData) {
            $jobExecution->addFailure($this->failureFromArray($failureData), false);
        }
        foreach ($jobExecutionData['warnings'] as $warningData) {
            $jobExecution->addWarning($this->warningFromArray($warningData), false);
        }

        /** @var JobExecutionDefinition $childExecutionData */
        foreach ($jobExecutionData['childExecutions'] as $childExecutionData) {
            $jobExecution->addChildExecution($this->fromArray($childExecutionData, $jobExecution));
        }

        return $jobExecution;
    }

    private function dateToString(DateTimeInterface|null $date): string|null
    {
        if ($date === null) {
            return null;
        }

        return $date->format(DateTimeInterface::ISO8601);
    }

    private function stringToDate(string|null $date): DateTimeInterface|null
    {
        if ($date === null) {
            return null;
        }

        $dateObject = DateTimeImmutable::createFromFormat(DateTimeInterface::ISO8601, $date);
        if ($dateObject === false) {
            throw UnexpectedValueException::date(DateTimeInterface::ISO8601, $date);
        }

        return $dateObject;
    }

    /**
     * @return FailureDefinition
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
     * @param FailureDefinition $array
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
     * @return WarningDefinition
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
     * @param WarningDefinition $array
     */
    private function warningFromArray(array $array): Warning
    {
        return new Warning($array['message'], $array['parameters'], $array['context']);
    }
}
