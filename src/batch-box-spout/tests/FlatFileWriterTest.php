<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Box\Spout;

use Box\Spout\Common\Type;
use Box\Spout\Reader\Wrapper\XMLReader;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\Box\Spout\FlatFileWriter;
use Yokai\Batch\Exception\BadMethodCallException;
use Yokai\Batch\Exception\CannotAccessParameterException;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Parameters\JobExecutionParameterAccessor;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\JobExecution;

class FlatFileWriterTest extends TestCase
{
    private const WRITE_DIR = ARTIFACT_DIR . '/flat-file-writer';

    public static function setUpBeforeClass(): void
    {
        if (!is_dir(self::WRITE_DIR)) {
            mkdir(self::WRITE_DIR, 0777, true);
        }
    }

    /**
     * @dataProvider types
     */
    public function testSomethingThatIsNotAnArray(string $type): void
    {
        $this->expectException(UnexpectedValueException::class);

        $file = self::WRITE_DIR . '/not-an-array.' . $type;

        $writer = new FlatFileWriter($type, new StaticValueParameterAccessor($file));
        $writer->setJobExecution(JobExecution::createRoot('123456789', 'export'));

        $writer->initialize();
        $writer->write([true]);
    }

    /**
     * @dataProvider combination
     */
    public function testWrite(
        string $type,
        string $filename,
        ?array $headers,
        iterable $itemsToWrite,
        string $expectedContent
    ): void {
        $file = self::WRITE_DIR . '/' . $filename;

        self::assertFileDoesNotExist($file);

        $writer = new FlatFileWriter($type, new StaticValueParameterAccessor($file), $headers);
        $writer->setJobExecution(JobExecution::createRoot('123456789', 'export'));

        $writer->initialize();
        $writer->write($itemsToWrite);
        $writer->flush();
        $this->assertFileContents($type, $file, $expectedContent);
    }

    /**
     * @dataProvider types
     */
    public function testShouldInitializeBeforeWrite(string $type): void
    {
        $this->expectException(BadMethodCallException::class);

        $writer = new FlatFileWriter($type, new StaticValueParameterAccessor('/path/to/file'));
        $writer->write([true]);
    }

    /**
     * @dataProvider types
     */
    public function testShouldInitializeBeforeFlush(string $type): void
    {
        $this->expectException(BadMethodCallException::class);

        $writer = new FlatFileWriter($type, new StaticValueParameterAccessor('/path/to/file'));
        $writer->flush();
    }

    /**
     * @dataProvider types
     */
    public function testMissingFileToWriter(string $type)
    {
        $this->expectException(CannotAccessParameterException::class);

        $reader = new FlatFileWriter($type, new JobExecutionParameterAccessor('undefined'));
        $reader->setJobExecution(JobExecution::createRoot('123456789', 'parent'));

        $reader->initialize();
    }

    public function types(): \Generator
    {
        foreach ([Type::CSV, Type::XLSX, Type::ODS] as $type) {
            yield [$type];
        }
    }

    public function combination(): \Generator
    {
        $headers = ['firstName', 'lastName'];
        $items = [
            ['John', 'Doe'],
            ['Jane', 'Doe'],
            ['Jack', 'Doe'],
        ];
        $content = <<<CSV
firstName,lastName
John,Doe
Jane,Doe
Jack,Doe
CSV;

        foreach ($this->types() as [$type]) {
            yield [
                $type,
                "header-in-items.$type",
                null,
                array_merge([$headers], $items),
                $content,
            ];
            yield [
                $type,
                "header-in-constructor.$type",
                $headers,
                $items,
                $content,
            ];
        }
    }

    private function assertFileContents(string $type, string $filePath, string $inlineData): void
    {
        $strings = array_merge(...array_map('str_getcsv', explode(PHP_EOL, $inlineData)));

        switch ($type) {
            case Type::CSV:
                $fileContents = file_get_contents($filePath);
                foreach ($strings as $string) {
                    self::assertStringContainsString($string, $fileContents);
                }
                break;

            case Type::XLSX:
                $pathToSheetFile = $filePath . '#xl/worksheets/sheet1.xml';
                $xmlContents = file_get_contents('zip://' . $pathToSheetFile);
                foreach ($strings as $string) {
                    self::assertStringContainsString($string, $xmlContents);
                }
                break;

            case Type::ODS:
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
