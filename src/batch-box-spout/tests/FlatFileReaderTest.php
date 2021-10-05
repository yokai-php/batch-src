<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Box\Spout;

use Box\Spout\Common\Type;
use Generator;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\Box\Spout\FlatFileReader;
use Yokai\Batch\Exception\CannotAccessParameterException;
use Yokai\Batch\Exception\InvalidArgumentException;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Parameters\JobExecutionParameterAccessor;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\JobExecution;

class FlatFileReaderTest extends TestCase
{
    /**
     * @dataProvider combination
     */
    public function testRead(
        string $type,
        string $headersMode,
        ?array $headers,
        array $expected,
        array $options = [],
        string $file = null
    ): void {
        $file ??= __DIR__ . '/fixtures/sample.' . $type;
        $jobExecution = JobExecution::createRoot('123456789', 'parent');
        $reader = new FlatFileReader($type, new StaticValueParameterAccessor($file), $options, $headersMode, $headers);
        $reader->setJobExecution($jobExecution);

        /** @var \Iterator $got */
        $got = $reader->read();
        self::assertInstanceOf(\Iterator::class, $got);
        self::assertSame($expected, iterator_to_array($got));
    }

    public function testInvalidType(): void
    {
        $this->expectException(UnexpectedValueException::class);

        new FlatFileReader('invalid type', new StaticValueParameterAccessor('/path/to/file'));
    }

    /**
     * @dataProvider types
     */
    public function testInvalidHeadersMode(string $type): void
    {
        $this->expectException(UnexpectedValueException::class);

        new FlatFileReader($type, new StaticValueParameterAccessor('/path/to/file'), [], 'invalid header mode');
    }

    /**
     * @dataProvider types
     */
    public function testInvalidHeadersCombineAndHeader(string $type): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FlatFileReader(
            $type,
            new StaticValueParameterAccessor('/path/to/file'),
            [],
            FlatFileReader::HEADERS_MODE_COMBINE,
            ['nom', 'prenom']
        );
    }

    /**
     * @dataProvider types
     */
    public function testMissingFileToRead(string $type): void
    {
        $this->expectException(CannotAccessParameterException::class);

        $reader = new FlatFileReader($type, new JobExecutionParameterAccessor('undefined'));
        $reader->setJobExecution(JobExecution::createRoot('123456789', 'parent'));

        iterator_to_array($reader->read());
    }

    public function testReadWrongLineSize(): void
    {
        $file = __DIR__ . '/fixtures/wrong-line-size.csv';
        $jobExecution = JobExecution::createRoot('123456789', 'parent');
        $reader = new FlatFileReader(
            'csv',
            new StaticValueParameterAccessor($file),
            [],
            FlatFileReader::HEADERS_MODE_COMBINE
        );
        $reader->setJobExecution($jobExecution);

        /** @var \Iterator $result */
        $result = $reader->read();
        self::assertInstanceOf(\Iterator::class, $result);
        self::assertSame(
            [
                ['firstName' => 'John', 'lastName' => 'Doe'],
                ['firstName' => 'Jack', 'lastName' => 'Doe'],
            ],
            iterator_to_array($result)
        );

        self::assertSame(
            'Expecting row {row} to have exactly {expected} columns(s), but got {actual}.',
            $jobExecution->getWarnings()[0]->getMessage()
        );
        self::assertSame(
            [
                '{row}' => '3',
                '{expected}' => '2',
                '{actual}' => '3',
            ],
            $jobExecution->getWarnings()[0]->getParameters()
        );
        self::assertSame(
            ['headers' => ['firstName', 'lastName'], 'row' => ['Jane', 'Doe', 'too much data']],
            $jobExecution->getWarnings()[0]->getContext()
        );
    }

    public function testReadCustomCSV(): void
    {
        $file = __DIR__ . '/fixtures/iso-8859-1.csv';
        $reader = new FlatFileReader(
            'csv',
            new StaticValueParameterAccessor($file),
            ['delimiter' => ';', 'enclosure' => '|', 'encoding' => 'ISO-8859-1'],
            FlatFileReader::HEADERS_MODE_NONE,
            null
        );
        $reader->setJobExecution(JobExecution::createRoot('123456789', 'parent'));

        self::assertSame([
            ['Gérard', 'À peu près'],
            ['Benoît', 'Bien-être'],
            ['Gaëlle', 'Ça va'],
        ], iterator_to_array($reader->read()));
    }

    public function types(): Generator
    {
        foreach ([Type::CSV, Type::XLSX, Type::ODS] as $type) {
            yield [$type];
        }
    }

    public function combination(): Generator
    {
        foreach ($this->types() as [$type]) {
            yield [
                $type,
                FlatFileReader::HEADERS_MODE_NONE,
                null,
                [
                    ['firstName', 'lastName'],
                    ['John', 'Doe'],
                    ['Jane', 'Doe'],
                    ['Jack', 'Doe'],
                ],
            ];
            yield [
                $type,
                FlatFileReader::HEADERS_MODE_SKIP,
                null,
                [
                    ['John', 'Doe'],
                    ['Jane', 'Doe'],
                    ['Jack', 'Doe'],
                ],
            ];
            yield [
                $type,
                FlatFileReader::HEADERS_MODE_COMBINE,
                null,
                [
                    ['firstName' => 'John', 'lastName' => 'Doe'],
                    ['firstName' => 'Jane', 'lastName' => 'Doe'],
                    ['firstName' => 'Jack', 'lastName' => 'Doe'],
                ],
            ];

            yield [
                $type,
                FlatFileReader::HEADERS_MODE_NONE,
                ['prenom', 'nom'],
                [
                    ['prenom' => 'firstName', 'nom' => 'lastName'],
                    ['prenom' => 'John', 'nom' => 'Doe'],
                    ['prenom' => 'Jane', 'nom' => 'Doe'],
                    ['prenom' => 'Jack', 'nom' => 'Doe'],
                ],
            ];
            yield [
                $type,
                FlatFileReader::HEADERS_MODE_SKIP,
                ['prenom', 'nom'],
                [
                    ['prenom' => 'John', 'nom' => 'Doe'],
                    ['prenom' => 'Jane', 'nom' => 'Doe'],
                    ['prenom' => 'Jack', 'nom' => 'Doe'],
                ],
            ];

            if ($type === Type::CSV) {
                yield [
                    $type,
                    FlatFileReader::HEADERS_MODE_COMBINE,
                    null,
                    [
                        ['firstName' => 'John', 'lastName' => 'Doe'],
                        ['firstName' => 'Jane', 'lastName' => 'Doe'],
                        ['firstName' => 'Jack', 'lastName' => 'Doe'],
                    ],
                    ['delimiter' => '|'],
                    __DIR__ . '/fixtures/sample-pipe.csv'
                ];
                yield [
                    $type,
                    FlatFileReader::HEADERS_MODE_NONE,
                    null,
                    [
                        ['firstName', 'lastName'],
                        ['John', 'Doe'],
                        ['Jane', 'Doe'],
                        ['Jack', 'Doe'],
                    ],
                    ['delimiter' => '|'],
                    __DIR__ . '/fixtures/sample-pipe.csv'
                ];
                yield [
                    $type,
                    FlatFileReader::HEADERS_MODE_SKIP,
                    null,
                    [
                        ['John', 'Doe'],
                        ['Jane', 'Doe'],
                        ['Jack', 'Doe'],
                    ],
                    ['delimiter' => '|'],
                    __DIR__ . '/fixtures/sample-pipe.csv'
                ];
                yield [
                    $type,
                    FlatFileReader::HEADERS_MODE_SKIP,
                    ['prenom', 'nom'],
                    [
                        ['prenom' => 'John', 'nom' => 'Doe'],
                        ['prenom' => 'Jane', 'nom' => 'Doe'],
                        ['prenom' => 'Jack', 'nom' => 'Doe'],
                    ],
                    ['delimiter' => '|'],
                    __DIR__ . '/fixtures/sample-pipe.csv'
                ];
            }
        }
    }
}
