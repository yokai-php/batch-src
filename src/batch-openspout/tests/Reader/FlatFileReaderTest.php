<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\OpenSpout\Reader;

use Generator;
use OpenSpout\Reader\CSV\Options as CSVOptions;
use OpenSpout\Reader\ODS\Options as ODSOptions;
use OpenSpout\Reader\XLSX\Options as XLSXOptions;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\OpenSpout\Reader\FlatFileReader;
use Yokai\Batch\Bridge\OpenSpout\Reader\HeaderStrategy;
use Yokai\Batch\Bridge\OpenSpout\Reader\SheetFilter;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\JobExecution;

class FlatFileReaderTest extends TestCase
{
    /**
     * @dataProvider sets
     */
    public function testRead(
        string $file,
        ?object $options,
        ?callable $sheetFilter,
        ?callable $headers,
        array $expected,
    ): void {
        $jobExecution = JobExecution::createRoot('123456789', 'parent');
        $reader = new FlatFileReader(
            new StaticValueParameterAccessor($file),
            $options,
            $sheetFilter ? $sheetFilter() : null,
            $headers ? $headers() : null
        );
        $reader->setJobExecution($jobExecution);

        /** @var \Iterator $got */
        $got = $reader->read();
        self::assertInstanceOf(\Iterator::class, $got);
        self::assertSame($expected, \iterator_to_array($got));
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
        foreach ([$csv, $ods, $xlsx] as $file) {
            yield [
                $file,
                null,
                null,
                fn() => HeaderStrategy::none(),
                $expected,
            ];
        }

        // first line is header and should be skipped
        $expected = [
            ['John', 'Doe'],
            ['Jane', 'Doe'],
            ['Jack', 'Doe'],
        ];
        foreach ([$csv, $ods, $xlsx] as $file) {
            yield [
                $file,
                null,
                null,
                fn() => HeaderStrategy::skip(),
                $expected,
            ];
        }

        // first line is header and should be skipped, but headers is provided with static value
        $expected = [
            ['prenom' => 'John', 'nom' => 'Doe'],
            ['prenom' => 'Jane', 'nom' => 'Doe'],
            ['prenom' => 'Jack', 'nom' => 'Doe'],
        ];
        foreach ([$csv, $ods, $xlsx] as $file) {
            yield [
                $file,
                null,
                null,
                fn() => HeaderStrategy::skip(['prenom', 'nom']),
                $expected,
            ];
        }

        // first line is header and should be skipped
        $expected = [
            ['firstName' => 'John', 'lastName' => 'Doe'],
            ['firstName' => 'Jane', 'lastName' => 'Doe'],
            ['firstName' => 'Jack', 'lastName' => 'Doe'],
        ];
        foreach ([$csv, $ods, $xlsx] as $file) {
            yield [
                $file,
                null,
                null,
                fn() => HeaderStrategy::combine(),
                $expected,
            ];
        }

        // non-standard CSV (delimiter and enclosure changed) encoded in ISO-8859
        $options = new CSVOptions();
        $options->FIELD_DELIMITER = ';';
        $options->FIELD_ENCLOSURE = '|';
        $options->ENCODING = 'ISO-8859-1';
        yield [
            __DIR__ . '/fixtures/iso-8859-1.csv',
            $options,
            null,
            null,
            [
                ['Gérard', 'À peu près'],
                ['Benoît', 'Bien-être'],
                ['Gaëlle', 'Ça va'],
            ],
        ];

        // change files to multi tab
        $ods = __DIR__ . '/fixtures/multi-tabs.ods';
        $xlsx = __DIR__ . '/fixtures/multi-tabs.xlsx';

        // multi-tab files, 1st tab
        $expected = [
            ['firstName' => 'John', 'lastName' => 'Doe'],
            ['firstName' => 'Jane', 'lastName' => 'Doe'],
            ['firstName' => 'Jack', 'lastName' => 'Doe'],
        ];
        foreach ([$ods, $xlsx] as $file) {
            yield [
                $file,
                null,
                fn() => SheetFilter::indexIs(0),
                fn() => HeaderStrategy::combine(),
                $expected,
            ];
        }

        // multi-tab files, tab "Français"
        $expected = [
            ['prénom' => 'Jean', 'nom' => 'Bon'],
            ['prénom' => 'Jeanne', 'nom' => 'Aimar'],
            ['prénom' => 'Jacques', 'nom' => 'Ouzi'],
        ];
        foreach ([$ods, $xlsx] as $file) {
            yield [
                $file,
                null,
                fn() => SheetFilter::nameIs('Français'),
                fn() => HeaderStrategy::combine(),
                $expected,
            ];
        }

        // multi-tab files, all tabs
        $expected = [
            ['firstName' => 'John', 'lastName' => 'Doe'],
            ['firstName' => 'Jane', 'lastName' => 'Doe'],
            ['firstName' => 'Jack', 'lastName' => 'Doe'],
            ['prénom' => 'Jean', 'nom' => 'Bon'],
            ['prénom' => 'Jeanne', 'nom' => 'Aimar'],
            ['prénom' => 'Jacques', 'nom' => 'Ouzi'],
        ];
        foreach ([$ods, $xlsx] as $file) {
            yield [
                $file,
                null,
                fn() => SheetFilter::all(),
                fn() => HeaderStrategy::combine(),
                $expected,
            ];
        }
    }

    public function testReadWrongLineSize(): void
    {
        $file = __DIR__ . '/fixtures/wrong-line-size.csv';
        $jobExecution = JobExecution::createRoot('123456789', 'parent');
        $reader = new FlatFileReader(
            new StaticValueParameterAccessor($file),
            null,
            null,
            HeaderStrategy::combine(),
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
            \iterator_to_array($result)
        );

        self::assertSame(
            'Expecting row 3 to have exactly 2 columns(s), but got 3.',
            $jobExecution->getWarnings()[0]->getMessage()
        );
        self::assertSame(
            ['headers' => ['firstName', 'lastName'], 'row' => ['Jane', 'Doe', 'too much data']],
            $jobExecution->getWarnings()[0]->getContext()
        );
    }

    /**
     * @dataProvider wrongOptions
     */
    public function testWrongOptions(string $file, object $options): void
    {
        $this->expectException(\TypeError::class);

        $jobExecution = JobExecution::createRoot('123456789', 'parent');
        $reader = new FlatFileReader(new StaticValueParameterAccessor($file), $options);
        $reader->setJobExecution($jobExecution);

        \iterator_to_array($reader->read());
    }

    public function wrongOptions(): \Generator
    {
        // with CSV file, CSVOptions is expected
        yield [__DIR__ . '/fixtures/sample.csv', new XLSXOptions()];
        yield [__DIR__ . '/fixtures/sample.csv', new ODSOptions()];

        // with ODS file, ODSOptions is expected
        yield [__DIR__ . '/fixtures/sample.ods', new CSVOptions()];
        yield [__DIR__ . '/fixtures/sample.ods', new XLSXOptions()];

        // with XLSX file, XLSXOptions is expected
        yield [__DIR__ . '/fixtures/sample.xlsx', new CSVOptions()];
        yield [__DIR__ . '/fixtures/sample.xlsx', new ODSOptions()];
    }
}
