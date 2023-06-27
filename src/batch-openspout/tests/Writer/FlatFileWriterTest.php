<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\OpenSpout\Writer;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Options as CSVOptions;
use OpenSpout\Writer\ODS\Options as ODSOptions;
use OpenSpout\Writer\XLSX\Options as XLSXOptions;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\OpenSpout\Writer\FlatFileWriter;
use Yokai\Batch\Bridge\OpenSpout\Writer\WriteToSheetItem;
use Yokai\Batch\Exception\BadMethodCallException;
use Yokai\Batch\Exception\RuntimeException;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\JobExecution;

class FlatFileWriterTest extends TestCase
{
    private const WRITE_DIR = ARTIFACT_DIR . '/openspout-flat-file-writer';

    /**
     * @dataProvider sets
     */
    public function testWrite(
        string $filename,
        ?object $options,
        ?string $defaultSheet,
        ?array $headers,
        iterable $itemsToWrite,
        string $expectedContent
    ): void {
        $file = self::WRITE_DIR . '/' . $filename;
        self::assertFileDoesNotExist($file);

        $writer = new FlatFileWriter(new StaticValueParameterAccessor($file), $options, $defaultSheet, $headers);
        $writer->setJobExecution(JobExecution::createRoot('123456789', 'export'));

        $writer->initialize();
        $writer->write($itemsToWrite);
        $writer->flush();

        self::assertFileContents($file, $expectedContent);
    }

    public function sets(): \Generator
    {
        $headers = ['firstName', 'lastName'];
        $items = [
            ['John', 'Doe'],
            ['Jane', 'Doe'],
            ['Jack', 'Doe'],
        ];
        $contentWithoutHeader = <<<CSV
John,Doe
Jane,Doe
Jack,Doe
CSV;
        $contentWithHeader = <<<CSV
firstName,lastName
John,Doe
Jane,Doe
Jack,Doe
CSV;

        foreach ($this->types() as [$type]) {
            yield [
                "no-header.$type",
                null,
                null,
                null,
                $items,
                $contentWithoutHeader,
            ];
            yield [
                "with-header.$type",
                null,
                null,
                $headers,
                $items,
                $contentWithHeader,
            ];
        }

        $options = new CSVOptions();
        $options->FIELD_DELIMITER = ';';
        $options->FIELD_ENCLOSURE = '|';
        $content = <<<CSV
John;Doe
Jane;Doe
Jack;Doe
CSV;
        yield [
            "custom.csv",
            $options,
            null,
            null,
            $items,
            $content,
        ];

        $style = (new Style())
            ->setFontBold()
            ->setFontSize(15)
            ->setFontColor(Color::BLUE)
            ->setShouldWrapText()
            ->setCellAlignment(CellAlignment::RIGHT)
            ->setBackgroundColor(Color::YELLOW);

        $options = new XLSXOptions();
        $options->DEFAULT_ROW_STYLE = $style;
        yield [
            "total-style.xlsx",
            $options,
            'Sheet1 with styles',
            null,
            $items,
            $contentWithoutHeader,
        ];
        $options = new ODSOptions();
        $options->DEFAULT_ROW_STYLE = $style;
        yield [
            "total-style.ods",
            $options,
            'Sheet1 with styles',
            null,
            $items,
            $contentWithoutHeader,
        ];

        $blue = (new Style())
            ->setFontBold()
            ->setFontColor(Color::BLUE);
        $red = (new Style())
            ->setFontBold()
            ->setFontColor(Color::RED);
        $green = (new Style())
            ->setFontBold()
            ->setFontColor(Color::GREEN);
        $styledItems = [
            Row::fromValues(['John', 'Doe'], $blue),
            Row::fromValues(['Jane', 'Doe'], $red),
            Row::fromValues(['Jack', 'Doe'], $green),
        ];
        yield [
            "partial-style.xlsx",
            null,
            null,
            null,
            $styledItems,
            $contentWithoutHeader,
        ];
        yield [
            "partial-style.ods",
            null,
            null,
            null,
            $styledItems,
            $contentWithoutHeader,
        ];
    }

    /**
     * @dataProvider types
     */
    public function testWriteInvalidItem(string $type): void
    {
        $this->expectException(UnexpectedValueException::class);

        $file = self::WRITE_DIR . '/invalid-item.' . $type;
        $writer = new FlatFileWriter(new StaticValueParameterAccessor($file));
        $writer->setJobExecution(JobExecution::createRoot('123456789', 'export'));

        $writer->initialize();
        $writer->write([true]); // writer accept collection of array or \OpenSpout\Common\Entity\Row
    }

    /**
     * @dataProvider types
     */
    public function testCannotCreateFile(string $type): void
    {
        $this->expectException(RuntimeException::class);

        $file = '/path/to/a/dir/that/do/not/exists/and/not/creatable/file.' . $type;
        $writer = new FlatFileWriter(new StaticValueParameterAccessor($file));
        $writer->setJobExecution(JobExecution::createRoot('123456789', 'export'));

        $writer->initialize();
    }

    /**
     * @dataProvider types
     */
    public function testShouldInitializeBeforeWrite(string $type): void
    {
        $this->expectException(BadMethodCallException::class);

        $file = self::WRITE_DIR . '/should-initialize-before-write.' . $type;
        $writer = new FlatFileWriter(new StaticValueParameterAccessor($file));
        $writer->write([true]);
    }

    /**
     * @dataProvider types
     */
    public function testShouldInitializeBeforeFlush(string $type): void
    {
        $this->expectException(BadMethodCallException::class);

        $file = self::WRITE_DIR . '/should-initialize-before-flush.' . $type;
        $writer = new FlatFileWriter(new StaticValueParameterAccessor($file));
        $writer->flush();
    }

    public function types(): \Generator
    {
        yield ['csv'];
        yield ['ods'];
        yield ['xlsx'];
    }

    /**
     * @dataProvider multipleSheets
     */
    public function testWriteMultipleSheets(string $type, ?string $defaultSheet): void
    {
        $file = self::WRITE_DIR . '/multiple-sheets.' . $type;
        self::assertFileDoesNotExist($file);

        $writer = new FlatFileWriter(new StaticValueParameterAccessor($file), null, $defaultSheet);
        $writer->setJobExecution(JobExecution::createRoot('123456789', 'export'));

        $writer->initialize();
        $writer->write([
            WriteToSheetItem::array('English', ['John', 'Doe']),
            WriteToSheetItem::array('Français', ['Jean', 'Aimar']),
            WriteToSheetItem::row('English', Row::fromValues(['Jack', 'Doe'])),
            WriteToSheetItem::row('Français', Row::fromValues(['Jacques', 'Ouzi'])),
        ]);
        $writer->flush();

        if ($type === 'csv') {
            self::assertFileContents($file, <<<CSV
            John,Doe
            Jean,Aimar
            Jack,Doe
            Jacques,Ouzi
            CSV);
        } else {
            self::assertSheetContents($file, 'English', <<<CSV
            John,Doe
            Jack,Doe
            CSV);
            self::assertSheetContents($file, 'Français', <<<CSV
            Jean,Aimar
            Jacques,Ouzi
            CSV);
        }
    }

    public function multipleSheets(): \Generator
    {
        yield ['csv', null];
        yield ['xlsx', 'English'];
        yield ['ods', 'English'];
    }

    /**
     * @dataProvider wrongOptions
     */
    public function testWrongOptions(string $type, object $options): void
    {
        $this->expectException(\TypeError::class);

        $file = self::WRITE_DIR . '/should-initialize-before-flush.' . $type;
        $jobExecution = JobExecution::createRoot('123456789', 'parent');
        $reader = new FlatFileWriter(new StaticValueParameterAccessor($file), $options);
        $reader->setJobExecution($jobExecution);
        $reader->initialize();
    }

    public function wrongOptions(): \Generator
    {
        // with CSV file, CSVOptions is expected
        yield ['csv', new XLSXOptions()];
        yield ['csv', new ODSOptions()];

        // with ODS file, ODSOptions is expected
        yield ['ods', new CSVOptions()];
        yield ['ods', new XLSXOptions()];

        // with XLSX file, XLSXOptions is expected
        yield ['xlsx', new CSVOptions()];
        yield ['xlsx', new ODSOptions()];
    }

    private static function assertFileContents(string $filePath, string $inlineData): void
    {
        $type = \strtolower(\pathinfo($filePath, PATHINFO_EXTENSION));
        $strings = array_merge(...array_map('str_getcsv', explode(PHP_EOL, $inlineData)));

        switch ($type) {
            case 'csv':
                $fileContents = file_get_contents($filePath);
                foreach ($strings as $string) {
                    self::assertStringContainsString($string, $fileContents);
                }
                break;

            case 'xlsx':
                $pathToSheetFile = $filePath . '#xl/worksheets/sheet1.xml';
                $xmlContents = file_get_contents('zip://' . $pathToSheetFile);
                foreach ($strings as $string) {
                    self::assertStringContainsString("<t>$string</t>", $xmlContents);
                }
                break;

            case 'ods':
                $sheetContent = file_get_contents('zip://' . $filePath . '#content.xml');
                if (!preg_match('#<table:table[^>]+>[\s\S]*?<\/table:table>#', $sheetContent, $matches)) {
                    self::fail('No sheet found in file "' . $filePath . '".');
                }
                $sheetXmlAsString = $matches[0];
                foreach ($strings as $string) {
                    self::assertStringContainsString("<text:p>$string</text:p>", $sheetXmlAsString);
                }
                break;
        }
    }

    private static function assertSheetContents(string $filePath, string $sheet, string $inlineData): void
    {
        $type = \strtolower(\pathinfo($filePath, PATHINFO_EXTENSION));
        $strings = array_merge(...array_map('str_getcsv', explode(PHP_EOL, $inlineData)));

        switch ($type) {
            case 'csv':
                $fileContents = file_get_contents($filePath);
                foreach ($strings as $string) {
                    self::assertStringContainsString($string, $fileContents);
                }
                break;

            case 'xlsx':
                $workbookContent = file_get_contents('zip://' . $filePath . '#xl/workbook.xml');
                if (!preg_match('#<sheet name="' . $sheet . '" sheetId="([0-9]+)"#', $workbookContent, $matches)) {
                    self::fail('Sheet ' . $sheet . ' was not found in file "' . $filePath . '".');
                }
                $sheetFilename = 'sheet' . $matches[1];
                $sheetContent = file_get_contents('zip://' . $filePath . '#xl/worksheets/' . $sheetFilename . '.xml');
                foreach ($strings as $string) {
                    self::assertStringContainsString("<t>$string</t>", $sheetContent);
                }
                break;

            case 'ods':
                $sheetContent = file_get_contents('zip://' . $filePath . '#content.xml');
                $regex = '#<table:table.+table:name="' . $sheet . '">[\s\S]*?<\/table:table>#';
                if (!preg_match($regex, $sheetContent, $matches)) {
                    self::fail('Sheet ' . $sheet . ' was not found in file "' . $filePath . '".');
                }
                $sheetXmlAsString = $matches[0];
                foreach ($strings as $string) {
                    self::assertStringContainsString("<text:p>$string</text:p>", $sheetXmlAsString);
                }
                break;
        }
    }
}
