<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Serializer;

use DateTime;
use DateTimeImmutable;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Yokai\Batch\Bridge\Symfony\Serializer\DenormalizeItemProcessor;
use Yokai\Batch\Job\Item\Exception\SkipItemException;
use Yokai\Batch\Job\Item\Exception\SkipItemOnError;
use Yokai\Batch\Tests\Bridge\Symfony\Serializer\Dummy\DummyNormalizer;
use Yokai\Batch\Tests\Bridge\Symfony\Serializer\Dummy\FailingNormalizer;

final class DenormalizeItemProcessorTest extends TestCase
{
    /**
     * @dataProvider sets
     */
    public function testProcess(string $type, ?string $format, array $context, $item, $expected): void
    {
        $denormalizer = new DummyNormalizer(true, $expected);
        $processor = new DenormalizeItemProcessor($denormalizer, $type, $format, $context);

        self::assertSame($expected, $processor->process($item));
    }

    /**
     * @dataProvider sets
     */
    public function testUnsupported(string $type, ?string $format, array $context, $item): void
    {
        $denormalizer = new DummyNormalizer(false, null);
        $processor = new DenormalizeItemProcessor($denormalizer, $type, $format, $context);

        $exception = null;
        try {
            $processor->process($item);
        } catch (SkipItemException $exception) {
            // just capture the exception
        }

        self::assertNotNull($exception, 'Processor has thrown an exception');
        $cause = $exception->getCause();
        self::assertInstanceOf(SkipItemOnError::class, $cause);
        /** @var SkipItemOnError $cause */
        self::assertSame('Unable to denormalize item. Not supported.', $cause->getError()->getMessage());
    }

    /**
     * @dataProvider sets
     */
    public function testException(string $type, ?string $format, array $context, $item): void
    {
        $denormalizer = new FailingNormalizer($exceptionThrown = new UnsupportedException());
        $processor = new DenormalizeItemProcessor($denormalizer, $type, $format, $context);

        $exception = null;
        try {
            $processor->process($item);
        } catch (SkipItemException $exception) {
            // just capture the exception
        }

        self::assertNotNull($exception, 'Processor has thrown an exception');
        $cause = $exception->getCause();
        self::assertInstanceOf(SkipItemOnError::class, $cause);
        /** @var SkipItemOnError $cause */
        self::assertSame($exceptionThrown, $cause->getError());
    }

    public function sets(): Generator
    {
        yield [
            'stdClass',
            null,
            [],
            ['foo' => 'bar'],
            \json_decode('{"foo":"bar"}'),
        ];
        yield [
            'DateTime',
            'json',
            [],
            '2020-01-01T12:00:00+02:00',
            DateTime::createFromFormat(\DATE_RFC3339, '2020-01-01T12:00:00+02:00'),
        ];
        yield [
            'DateTimeImmutable',
            'xml',
            ['datetime_format' => \DATE_RSS],
            'Wed, 01 Jan 2020 12:00:00 +0200',
            DateTimeImmutable::createFromFormat(\DATE_RSS, 'Wed, 01 Jan 2020 12:00:00 +0200'),
        ];
    }
}
