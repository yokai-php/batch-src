<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Box\Spout\Writer;

use Box\Spout\Common\Entity\Style\CellAlignment;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Reader\Wrapper\XMLReader;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\Box\Spout\Writer\FlatFileWriter;
use Yokai\Batch\Bridge\Box\Spout\Writer\Options\CSVOptions;
use Yokai\Batch\Bridge\Box\Spout\Writer\Options\ODSOptions;
use Yokai\Batch\Bridge\Box\Spout\Writer\Options\XLSXOptions;
use Yokai\Batch\Exception\BadMethodCallException;
use Yokai\Batch\Exception\RuntimeException;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\JobExecution;

class FlatFileWriterTest extends TestCase
{
    private const WRITE_DIR = ARTIFACT_DIR . '/flat-file-writer';

    /**
     * @dataProvider sets
     */
    public function testWrite(
        string $filename,
        callable $options,
        ?array $headers,
        iterable $itemsToWrite,
        string $expectedContent
    ): void {
        $file = self::WRITE_DIR . '/' . $filename;

        self::assertFileDoesNotExist($file);

        $writer = new FlatFileWriter(new StaticValueParameterAccessor($file), $options(), $headers);
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

        foreach ($this->types() as [$type, $options]) {
            yield [
                "no-header.$type",
                $options,
                null,
                $items,
                $contentWithoutHeader,
            ];
            yield [
                "with-header.$type",
                $options,
                $headers,
                $items,
                $contentWithHeader,
            ];
        }

        $content = <<<CSV
John;Doe
Jane;Doe
Jack;Doe
CSV;
        yield [
            "custom.csv",
            fn() => new CSVOptions(';', '|'),
            null,
            $items,
            $content,
        ];

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(15)
            ->setFontColor(Color::BLUE)
            ->setShouldWrapText()
            ->setCellAlignment(CellAlignment::RIGHT)
            ->setBackgroundColor(Color::YELLOW)
            ->build();

        yield [
            "total-style.xlsx",
            fn() => new XLSXOptions('Sheet1 with styles', $style),
            null,
            $items,
            $contentWithoutHeader,
        ];
        yield [
            "total-style.ods",
            fn() => new ODSOptions('Sheet1 with styles', $style),
            null,
            $items,
            $contentWithoutHeader,
        ];

        $blue = (new StyleBuilder())
            ->setFontBold()
            ->setFontColor(Color::BLUE)
            ->build();
        $red = (new StyleBuilder())
            ->setFontBold()
            ->setFontColor(Color::RED)
            ->build();
        $green = (new StyleBuilder())
            ->setFontBold()
            ->setFontColor(Color::GREEN)
            ->build();
        $styledItems = [
            WriterEntityFactory::createRowFromArray(['John', 'Doe'], $blue),
            WriterEntityFactory::createRowFromArray(['Jane', 'Doe'], $red),
            WriterEntityFactory::createRowFromArray(['Jack', 'Doe'], $green),
        ];
        yield [
            "partial-style.xlsx",
            fn() => new XLSXOptions(),
            null,
            $styledItems,
            $contentWithoutHeader,
        ];
        yield [
            "partial-style.ods",
            fn() => new ODSOptions(),
            null,
            $styledItems,
            $contentWithoutHeader,
        ];
    }

    /**
     * @dataProvider types
     */
    public function testWriteInvalidItem(string $type, callable $options): void
    {
        $this->expectException(UnexpectedValueException::class);

        $file = self::WRITE_DIR . '/invalid-item.' . $type;
        $writer = new FlatFileWriter(new StaticValueParameterAccessor($file), $options());
        $writer->setJobExecution(JobExecution::createRoot('123456789', 'export'));

        $writer->initialize();
        $writer->write([true]); // writer accept collection of array or \Box\Spout\Common\Entity\Row
    }

    /**
     * @dataProvider types
     */
    public function testCannotCreateFile(string $type, callable $options): void
    {
        $this->expectException(RuntimeException::class);

        $file = '/path/to/a/dir/that/do/not/exists/and/not/creatable/file.' . $type;
        $writer = new FlatFileWriter(new StaticValueParameterAccessor($file), $options());
        $writer->setJobExecution(JobExecution::createRoot('123456789', 'export'));

        $writer->initialize();
    }

    /**
     * @dataProvider types
     */
    public function testShouldInitializeBeforeWrite(string $type, callable $options): void
    {
        $this->expectException(BadMethodCallException::class);

        $file = self::WRITE_DIR . '/should-initialize-before-write.' . $type;
        $writer = new FlatFileWriter(new StaticValueParameterAccessor($file), $options());
        $writer->write([true]);
    }

    /**
     * @dataProvider types
     */
    public function testShouldInitializeBeforeFlush(string $type, callable $options): void
    {
        $this->expectException(BadMethodCallException::class);

        $file = self::WRITE_DIR . '/should-initialize-before-flush.' . $type;
        $writer = new FlatFileWriter(new StaticValueParameterAccessor($file), $options());
        $writer->flush();
    }

    public function types(): \Generator
    {
        $types = [
            'csv' => fn() => new CSVOptions(),
            'xlsx' => fn() => new XLSXOptions(),
            'ods' => fn() => new ODSOptions(),
        ];
        foreach ($types as $type => $options) {
            yield [$type, $options];
        }
    }
    /**
     * @dataProvider wrongOptions
     */
    public function testWrongOptions(string $type, callable $options): void
    {
        $this->expectException(UnexpectedValueException::class);

        $file = self::WRITE_DIR . '/should-initialize-before-flush.' . $type;
        $jobExecution = JobExecution::createRoot('123456789', 'parent');
        $reader = new FlatFileWriter(new StaticValueParameterAccessor($file), $options());
        $reader->setJobExecution($jobExecution);
        $reader->initialize();
    }

    public function wrongOptions(): \Generator
    {
        // with CSV file, CSVOptions is expected
        yield [
            'csv',
            fn() => new XLSXOptions(),
        ];
        yield [
            'csv',
            fn() => new ODSOptions(),
        ];

        // with ODS file, ODSOptions is expected
        yield [
            'ods',
            fn() => new CSVOptions(),
        ];
        yield [
            'ods',
            fn() => new XLSXOptions(),
        ];

        // with XLSX file, XLSXOptions is expected
        yield [
            'xlsx',
            fn() => new CSVOptions(),
        ];
        yield [
            'xlsx',
            fn() => new ODSOptions(),
        ];
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
                    self::assertStringContainsString($string, $xmlContents);
                }
                break;

            case 'ods':
                $xmlReader = new XMLReader();
                $xmlReader->openFileInZip($filePath, 'content.xml');
                $xmlReader->readUntilNodeFound('table:table');
                $sheetXmlAsString = $xmlReader->readOuterXml();
                foreach ($strings as $string) {
                    self::assertStringContainsString("<text:p>$string</text:p>", $sheetXmlAsString);
                }
                break;
        }
    }
}
