<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Serializer;

use DateTime;
use DateTimeImmutable;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\BadMethodCallException;
use Yokai\Batch\Bridge\Symfony\Serializer\NormalizeItemProcessor;
use Yokai\Batch\Job\Item\Exception\SkipItemException;
use Yokai\Batch\Job\Item\Exception\SkipItemOnError;
use Yokai\Batch\Tests\Bridge\Symfony\Serializer\Dummy\DummyNormalizer;
use Yokai\Batch\Tests\Bridge\Symfony\Serializer\Dummy\FailingNormalizer;

final class NormalizeItemProcessorTest extends TestCase
{
    /**
     * @dataProvider sets
     */
    public function testProcess(?string $format, array $context, $item, $expected): void
    {
        $normalizer = new DummyNormalizer(true, $expected);
        $processor = new NormalizeItemProcessor($normalizer, $format, $context);

        self::assertSame($expected, $processor->process($item));
    }

    /**
     * @dataProvider sets
     */
    public function testUnsupported(?string $format, array $context, $item): void
    {
        $normalizer = new DummyNormalizer(false, null);
        $processor = new NormalizeItemProcessor($normalizer, $format, $context);

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
        self::assertSame('Unable to normalize item. Not supported.', $cause->getError()->getMessage());
    }

    /**
     * @dataProvider sets
     */
    public function testException(?string $format, array $context, $item): void
    {
        $normalizer = new FailingNormalizer($exceptionThrown = new BadMethodCallException());
        $processor = new NormalizeItemProcessor($normalizer, $format, $context);

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
            null,
            [],
            \json_decode('{"foo":"bar"}'),
            ['foo' => 'bar'],
        ];
        yield [
            'json',
            [],
            DateTime::createFromFormat(\DATE_RFC3339, '2020-01-01T12:00:00+02:00'),
            '2020-01-01T12:00:00+02:00',
        ];
        yield [
            'xml',
            ['datetime_format' => \DATE_RSS],
            DateTimeImmutable::createFromFormat(\DATE_RSS, 'Wed, 01 Jan 2020 12:00:00 +0200'),
            'Wed, 01 Jan 2020 12:00:00 +0200',
        ];
    }
}
