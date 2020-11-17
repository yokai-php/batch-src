<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Box\Spout;

use Box\Spout\Common\Type;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\Box\Spout\FlatFileReader;
use Yokai\Batch\Exception\InvalidArgumentException;
use Yokai\Batch\Exception\UndefinedJobParameterException;
use Yokai\Batch\JobExecution;
use Yokai\Batch\JobParameters;

class FlatFileReaderTest extends TestCase
{
    /**
     * @dataProvider combination
     */
    public function testRead(string $type, string $headersMode, ?array $headers, array $expected)
    {
        $jobExecution = JobExecution::createRoot(
            '123456789',
            'parent',
            null,
            new JobParameters([FlatFileReader::SOURCE_FILE_PARAMETER => __DIR__ . '/fixtures/sample.' . $type])
        );
        $reader = new FlatFileReader($type, [], $headersMode, $headers);
        $reader->setJobExecution($jobExecution);

        /** @var \Iterator $got */
        $got = $reader->read();
        self::assertInstanceOf(\Iterator::class, $got);
        self::assertSame($expected, iterator_to_array($got));
    }

    /**
     * @dataProvider types
     */
    public function testInvalidConstruction(string $type)
    {
        $this->expectException(InvalidArgumentException::class);

        new FlatFileReader($type, [], FlatFileReader::HEADERS_MODE_COMBINE, ['nom', 'prenom']);
    }

    /**
     * @dataProvider types
     */
    public function testMissingFileToRead(string $type)
    {
        $this->expectException(UndefinedJobParameterException::class);

        $reader = new FlatFileReader($type);
        $reader->setJobExecution(JobExecution::createRoot('123456789', 'parent'));

        iterator_to_array($reader->read());
    }

    public function types()
    {
        foreach ([Type::CSV, Type::XLSX, Type::ODS] as $type) {
            yield [$type];
        }
    }

    public function combination()
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
        }
    }
}
