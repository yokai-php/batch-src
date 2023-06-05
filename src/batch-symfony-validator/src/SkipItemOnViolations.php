<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Validator;

use DateTimeInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Yokai\Batch\Job\Item\Exception\SkipItemCauseInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Warning;

/**
 * Skip item when validation fails and leave a warning with violations to the {@see JobExecution}.
 */
final class SkipItemOnViolations implements SkipItemCauseInterface
{
    public function __construct(
        /**
         * @phpstan-var ConstraintViolationListInterface<ConstraintViolationInterface>
         */
        private ConstraintViolationListInterface $violations
    ) {
    }

    public function report(JobExecution $execution, int|string $index, mixed $item): void
    {
        $execution->getSummary()->increment('invalid');
        $violations = [];
        /** @var ConstraintViolationInterface $violation */
        foreach ($this->violations as $violation) {
            $violations[] = \sprintf(
                '%s: %s (invalid value: %s)',
                $violation->getPropertyPath(),
                $violation->getMessage(),
                $this->normalizeInvalidValue($violation->getInvalidValue())
            );
        }

        $execution->addWarning(
            new Warning(
                'Violations were detected by validator.',
                [],
                ['itemIndex' => $index, 'item' => $item, 'violations' => $violations]
            )
        );
    }

    /**
     * @phpstan-return ConstraintViolationListInterface<ConstraintViolationInterface>
     */
    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }

    private function normalizeInvalidValue(mixed $invalidValue): string
    {
        if ($invalidValue === '') {
            return '""';
        }
        if ($invalidValue === null) {
            return 'NULL';
        }
        if (\is_scalar($invalidValue)) {
            return (string)$invalidValue;
        }

        if (\is_iterable($invalidValue)) {
            $invalidValues = [];
            foreach ($invalidValue as $value) {
                $invalidValues[] = $this->normalizeInvalidValue($value);
            }

            return \implode(', ', $invalidValues);
        }

        if (\is_object($invalidValue)) {
            if ($invalidValue instanceof DateTimeInterface) {
                return $invalidValue->format(DateTimeInterface::ATOM);
            }

            if (\method_exists($invalidValue, '__toString')) {
                return (string)$invalidValue;
            }

            return $invalidValue::class;
        }

        return \gettype($invalidValue);
    }
}
