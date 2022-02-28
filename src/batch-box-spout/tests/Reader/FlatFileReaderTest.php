<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Box\Spout\Reader;

use Generator;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\Box\Spout\Reader\FlatFileReader;
use Yokai\Batch\Bridge\Box\Spout\Reader\HeaderStrategy;
use Yokai\Batch\Bridge\Box\Spout\Reader\Options\CSVOptions;
use Yokai\Batch\Bridge\Box\Spout\Reader\Options\ODSOptions;
use Yokai\Batch\Bridge\Box\Spout\Reader\Options\SheetFilter;
use Yokai\Batch\Bridge\Box\Spout\Reader\Options\XLSXOptions;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\JobExecution;

class FlatFileReaderTest extends TestCase
{
    /**
     * @dataProvider sets
     */
    public function testRead(string $file, callable $options, callable $headers, array $expected): void
    {
        $jobExecution = JobExecution::createRoot('123456789', 'parent');
        $reader = new FlatFileReader(new StaticValueParameterAccessor($file), $options(), $headers());
        $reader->setJobExecution($jobExecution);

        /** @var \Iterator $got */
        $got = $reader->read();
        self::assertInstanceOf(\Iterator::class, $got);
        self::assertSame($expected, iterator_to_array($got));
    }

    public function sets(): Generator
    {
        $csv = __DIR__ . '/fixtures/sample.csv';
        $ods = __DIR__ . '/fixtures/sample.ods';
        $xlsx = __DIR__ . '/fixtures/sample.xlsx';

        // first line is not header
        $expected = [
            ['firstName', 'lastName'],
            ['John', 'Doe'],
            ['Jane', 'Doe'],
            ['Jack', 'Doe'],
        ];
        yield [
            $csv,
            fn() => new CSVOptions(),
            fn() => HeaderStrategy::none(),
            $expected,
        ];
        yield [
            $ods,
            fn() => new ODSOptions(),
            fn() => HeaderStrategy::none(),
            $expected,
        ];
        yield [
            $xlsx,
            fn() => new XLSXOptions(),
            fn() => HeaderStrategy::none(),
            $expected,
        ];

        // first line is header and should be skipped
        $expected = [
            ['John', 'Doe'],
            ['Jane', 'Doe'],
            ['Jack', 'Doe'],
        ];
        yield [
            $csv,
            fn() => new CSVOptions(),
            fn() => HeaderStrategy::skip(),
            $expected,
        ];
        yield [
            $ods,
            fn() => new ODSOptions(),
            fn() => HeaderStrategy::skip(),
            $expected,
        ];
        yield [
            $xlsx,
            fn() => new XLSXOptions(),
            fn() => HeaderStrategy::skip(),
            $expected,
        ];

        // first line is header and should be skipped, but headers is provided with static value
        $expected = [
            ['prenom' => 'John', 'nom' => 'Doe'],
            ['prenom' => 'Jane', 'nom' => 'Doe'],
            ['prenom' => 'Jack', 'nom' => 'Doe'],
        ];
        yield [
            $csv,
            fn() => new CSVOptions(),
            fn() => HeaderStrategy::skip(['prenom', 'nom']),
            $expected,
        ];
        yield [
            $ods,
            fn() => new ODSOptions(),
            fn() => HeaderStrategy::skip(['prenom', 'nom']),
            $expected,
        ];
        yield [
            $xlsx,
            fn() => new XLSXOptions(),
            fn() => HeaderStrategy::skip(['prenom', 'nom']),
            $expected,
        ];

        // first line is header and should be skipped
        $expected = [
            ['firstName' => 'John', 'lastName' => 'Doe'],
            ['firstName' => 'Jane', 'lastName' => 'Doe'],
            ['firstName' => 'Jack', 'lastName' => 'Doe'],
        ];
        yield [
            $csv,
            fn() => new CSVOptions(),
            fn() => HeaderStrategy::combine(),
            $expected,
        ];
        yield [
            $ods,
            fn() => new ODSOptions(),
            fn() => HeaderStrategy::combine(),
            $expected,
        ];
        yield [
            $xlsx,
            fn() => new XLSXOptions(),
            fn() => HeaderStrategy::combine(),
            $expected,
        ];

        // non-standard CSV (delimiter and enclosure changed) encoded in ISO-8859
        yield [
            __DIR__ . '/fixtures/iso-8859-1.csv',
            fn() => new CSVOptions(';', '|', 'ISO-8859-1'),
            fn() => HeaderStrategy::none(),
            [
                ['Gérard', 'À peu près'],
                ['Benoît', 'Bien-être'],
                ['Gaëlle', 'Ça va'],
            ],
        ];

        // multi-tab files, 1st tab
        $expected = [
            ['firstName' => 'John', 'lastName' => 'Doe'],
            ['firstName' => 'Jane', 'lastName' => 'Doe'],
            ['firstName' => 'Jack', 'lastName' => 'Doe'],
        ];
        yield [
            __DIR__ . '/fixtures/multi-tabs.ods',
            fn() => new ODSOptions(SheetFilter::indexIs(0)),
            fn() => HeaderStrategy::combine(),
            $expected,
        ];
        yield [
            __DIR__ . '/fixtures/multi-tabs.xlsx',
            fn() => new XLSXOptions(SheetFilter::indexIs(0)),
            fn() => HeaderStrategy::combine(),
            $expected,
        ];

        // multi-tab files, tab "Français"
        $expected = [
            ['prénom' => 'Jean', 'nom' => 'Bon'],
            ['prénom' => 'Jeanne', 'nom' => 'Aimar'],
            ['prénom' => 'Jacques', 'nom' => 'Ouzi'],
        ];
        yield [
            __DIR__ . '/fixtures/multi-tabs.ods',
            fn() => new ODSOptions(SheetFilter::nameIs('Français')),
            fn() => HeaderStrategy::combine(),
            $expected,
        ];
        yield [
            __DIR__ . '/fixtures/multi-tabs.xlsx',
            fn() => new XLSXOptions(SheetFilter::nameIs('Français')),
            fn() => HeaderStrategy::combine(),
            $expected,
        ];

        // multi-tab files, all tabs
        $expected = [
            ['firstName' => 'John', 'lastName' => 'Doe'],
            ['firstName' => 'Jane', 'lastName' => 'Doe'],
            ['firstName' => 'Jack', 'lastName' => 'Doe'],
            ['prénom' => 'Jean', 'nom' => 'Bon'],
            ['prénom' => 'Jeanne', 'nom' => 'Aimar'],
            ['prénom' => 'Jacques', 'nom' => 'Ouzi'],
        ];
        yield [
            __DIR__ . '/fixtures/multi-tabs.ods',
            fn() => new ODSOptions(),
            fn() => HeaderStrategy::combine(),
            $expected,
        ];
        yield [
            __DIR__ . '/fixtures/multi-tabs.xlsx',
            fn() => new XLSXOptions(),
            fn() => HeaderStrategy::combine(),
            $expected,
        ];
    }

    public function testReadWrongLineSize(): void
    {
        $file = __DIR__ . '/fixtures/wrong-line-size.csv';
        $jobExecution = JobExecution::createRoot('123456789', 'parent');
        $reader = new FlatFileReader(
            new StaticValueParameterAccessor($file),
            new CSVOptions(),
            HeaderStrategy::combine()
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

    /**
     * @dataProvider wrongOptions
     */
    public function testWrongOptions(string $file, callable $options): void
    {
        $this->expectException(UnexpectedValueException::class);

        $jobExecution = JobExecution::createRoot('123456789', 'parent');
        $reader = new FlatFileReader(new StaticValueParameterAccessor($file), $options());
        $reader->setJobExecution($jobExecution);

        iterator_to_array($reader->read());
    }

    public function wrongOptions(): \Generator
    {
        // with CSV file, CSVOptions is expected
        yield [
            __DIR__ . '/fixtures/sample.csv',
            fn() => new XLSXOptions(),
        ];
        yield [
            __DIR__ . '/fixtures/sample.csv',
            fn() => new ODSOptions(),
        ];

        // with ODS file, ODSOptions is expected
        yield [
            __DIR__ . '/fixtures/sample.ods',
            fn() => new CSVOptions(),
        ];
        yield [
            __DIR__ . '/fixtures/sample.ods',
            fn() => new XLSXOptions(),
        ];

        // with XLSX file, XLSXOptions is expected
        yield [
            __DIR__ . '/fixtures/sample.xlsx',
            fn() => new CSVOptions(),
        ];
        yield [
            __DIR__ . '/fixtures/sample.xlsx',
            fn() => new ODSOptions(),
        ];
    }
}
