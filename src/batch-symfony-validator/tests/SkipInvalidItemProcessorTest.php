<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Validator;

use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;
use Yokai\Batch\Bridge\Symfony\Validator\SkipInvalidItemProcessor;
use Yokai\Batch\Bridge\Symfony\Validator\SkipItemOnViolations;
use Yokai\Batch\Job\Item\Exception\SkipItemException;

class SkipInvalidItemProcessorTest extends TestCase
{
    /**
     * @dataProvider groups
     */
    public function testProcessValid(?array $groups): void
    {
        $validator = Validation::createValidator();
        $processor = new SkipInvalidItemProcessor($validator, [new NotBlank(['groups' => $groups])], $groups);
        self::assertSame('valid item not blank', $processor->process('valid item not blank'));
    }

    /**
     * @dataProvider groups
     */
    public function testProcessInvalid(?array $groups): void
    {
        $validator = Validation::createValidator();
        $processor = new SkipInvalidItemProcessor($validator, [new Blank(['groups' => ['Default', 'Full']])], $groups);

        $exception = null;
        try {
            $processor->process('invalid item not blank');
        } catch (SkipItemException $exception) {
            // just capture the exception
        }

        self::assertNotNull($exception, 'Processor has thrown an exception');

        self::assertSame(['constraints' => [Blank::class], 'groups' => $groups], $exception->getContext());
        $cause = $exception->getCause();
        self::assertInstanceOf(SkipItemOnViolations::class, $cause);
        /** @var SkipItemOnViolations $cause */
        /** @var ConstraintViolationInterface[] $violations */
        $violations = \iterator_to_array($cause->getViolations());
        self::assertCount(1, $violations);
        $violation = $violations[0];
        self::assertSame('', $violation->getPropertyPath());
        self::assertSame('This value should be blank.', $violation->getMessage());
        self::assertSame('invalid item not blank', $violation->getInvalidValue());
    }

    public function groups(): Generator
    {
        yield 'No groups specified' => [null];
        yield 'Group "Full" only' => [['Full']];
    }
}
