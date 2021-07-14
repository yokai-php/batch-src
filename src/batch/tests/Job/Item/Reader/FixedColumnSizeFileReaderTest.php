<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Job\Item\Reader;

use Generator;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Exception\UndefinedJobParameterException;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Item\Reader\FixedColumnSizeFileReader;
use Yokai\Batch\JobExecution;
use Yokai\Batch\JobParameters;

class FixedColumnSizeFileReaderTest extends TestCase
{
    /**
     * @dataProvider config
     */
    public function test(array $columns, string $headersMode, array $expected): void
    {
        // accessing file via constructor argument
        $reader = new FixedColumnSizeFileReader($columns, $headersMode, __DIR__ . '/fixtures/fixed-column-size.txt');
        self::assertSame($expected, \iterator_to_array($reader->read()));

        // accessing file via job execution parameter
        $execution = JobExecution::createRoot(
            '123456',
            'testing',
            null,
            new JobParameters(
                [FixedColumnSizeFileReader::SOURCE_FILE_PARAMETER => __DIR__ . '/fixtures/fixed-column-size.txt']
            )
        );
        $reader = new FixedColumnSizeFileReader($columns, $headersMode);
        $reader->setJobExecution($execution);
        self::assertSame($expected, \iterator_to_array($reader->read()));
    }

    public function testInvalidHeaderMode(): void
    {
        $this->expectException(UnexpectedValueException::class);
        new FixedColumnSizeFileReader([10, 10], 'wrong header mode');
    }

    public function testFileNotFound(): void
    {
        $this->expectException(UndefinedJobParameterException::class);
        $execution = JobExecution::createRoot('123456', 'testing');
        $reader = new FixedColumnSizeFileReader([10, 10]);
        $reader->setJobExecution($execution);
        \iterator_to_array($reader->read());
    }

    public function config(): Generator
    {
        $columnsWithoutNames = [10, 9, 8, -1];
        $columnsWithNames = ['firstName' => 10, 'lastName' => 9, 'country' => 8, 'city' => -1];

        yield [
            $columnsWithoutNames,
            FixedColumnSizeFileReader::HEADERS_MODE_COMBINE,
            [
                ['firstName' => 'John', 'lastName' => 'Doe', 'country' => 'USA', 'city' => 'Washington'],
                ['firstName' => 'Jane', 'lastName' => 'Doe', 'country' => 'USA', 'city' => 'Seattle'],
                ['firstName' => 'Jack', 'lastName' => 'Doe', 'country' => 'USA', 'city' => 'San Francisco'],
            ]
        ];
        yield [
            $columnsWithNames,
            FixedColumnSizeFileReader::HEADERS_MODE_SKIP,
            [
                ['firstName' => 'John', 'lastName' => 'Doe', 'country' => 'USA', 'city' => 'Washington'],
                ['firstName' => 'Jane', 'lastName' => 'Doe', 'country' => 'USA', 'city' => 'Seattle'],
                ['firstName' => 'Jack', 'lastName' => 'Doe', 'country' => 'USA', 'city' => 'San Francisco'],
            ]
        ];
        yield [
            $columnsWithoutNames,
            FixedColumnSizeFileReader::HEADERS_MODE_NONE,
            [
                ['firstName', 'lastName', 'country', 'city'],
                ['John', 'Doe', 'USA', 'Washington'],
                ['Jane', 'Doe', 'USA', 'Seattle'],
                ['Jack', 'Doe', 'USA', 'San Francisco'],
            ]
        ];
        yield [
            $columnsWithoutNames,
            FixedColumnSizeFileReader::HEADERS_MODE_SKIP,
            [
                ['John', 'Doe', 'USA', 'Washington'],
                ['Jane', 'Doe', 'USA', 'Seattle'],
                ['Jack', 'Doe', 'USA', 'San Francisco'],
            ]
        ];
    }
}
