<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Validator;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Yokai\Batch\Bridge\Symfony\Validator\SkipItemOnViolations;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Tests\Bridge\Symfony\Validator\Fixtures\EmptyClass;
use Yokai\Batch\Tests\Bridge\Symfony\Validator\Fixtures\StringableClass;

class SkipItemOnViolationsTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function test(array $violations, array $expectedViolations): void
    {
        $execution = JobExecution::createRoot('123', 'testing');
        $cause = new SkipItemOnViolations(new ConstraintViolationList($violations));
        $cause->report($execution, 'itemIndex', 'item');

        self::assertCount(1, $execution->getWarnings());
        self::assertSame('Violations were detected by validator.', $execution->getWarnings()[0]->getMessage());
        self::assertSame([], $execution->getWarnings()[0]->getParameters());
        self::assertSame(
            ['itemIndex' => 'itemIndex', 'item' => 'item', 'violations' => $expectedViolations],
            $execution->getWarnings()[0]->getContext()
        );
    }

    public function provider(): \Generator
    {
        $violation = function (string $message, $value) {
            return new ConstraintViolation($message, $message, [], null, 'property.path', $value);
        };
        $message = function (string $message, string $value) {
            return "property.path: $message (invalid value: $value)";
        };

        yield 'empty string' => [
            [$violation('This value should not be blank.', '')],
            [$message('This value should not be blank.', '""')],
        ];
        yield 'null' => [
            [$violation('This value should not be null.', null)],
            [$message('This value should not be null.', 'NULL')],
        ];
        yield 'string' => [
            [$violation('This value should be null.', 'string')],
            [$message('This value should be null.', 'string')],
        ];
        yield 'int' => [
            [$violation('This value should be null.', 1)],
            [$message('This value should be null.', '1')],
        ];
        yield 'array' => [
            [$violation('This collection should contain exactly 0 elements.', [1, 2])],
            [$message('This collection should contain exactly 0 elements.', '1, 2')],
        ];
        yield 'date' => [
            [$violation('This value should be null.', new DateTimeImmutable('2021-09-23T12:09:32+02:00'))],
            [$message('This value should be null.', '2021-09-23T12:09:32+02:00')],
        ];
        yield 'Stringable object' => [
            [$violation('This value should be null.', new StringableClass())],
            [$message('This value should be null.', '__toString')],
        ];
        yield 'not Stringable object' => [
            [$violation('This value should be null.', new EmptyClass())],
            [$message('This value should be null.', EmptyClass::class)],
        ];
        yield 'resource' => [
            [$violation('This value should be null.', \fopen(__FILE__, 'r'))],
            [$message('This value should be null.', 'resource')],
        ];
    }
}
