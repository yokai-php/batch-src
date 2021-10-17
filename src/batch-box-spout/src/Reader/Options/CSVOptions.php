<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Reader\Options;

use Box\Spout\Reader\CSV\Reader as CSVReader;
use Box\Spout\Reader\ReaderInterface;
use Yokai\Batch\Exception\UnexpectedValueException;

/**
 * Options for reading CSV files.
 */
final class CSVOptions implements OptionsInterface
{
    private string $delimiter;
    private string $enclosure;
    private string $encoding;
    private bool $formatDates;
    private bool $preserveEmptyRows;

    public function __construct(
        string $delimiter = ',',
        string $enclosure = '"',
        string $encoding = 'UTF-8',
        bool $formatDates = false,
        bool $preserveEmptyRows = false
    ) {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->encoding = $encoding;
        $this->formatDates = $formatDates;
        $this->preserveEmptyRows = $preserveEmptyRows;
    }

    /**
     * @inheritdoc
     */
    public function configure(ReaderInterface $reader): void
    {
        if (!$reader instanceof CSVReader) {
            throw UnexpectedValueException::type(CSVReader::class, $reader);
        }

        $reader->setFieldDelimiter($this->delimiter);
        $reader->setFieldEnclosure($this->enclosure);
        $reader->setEncoding($this->encoding);
        $reader->setShouldFormatDates($this->formatDates);
        $reader->setShouldPreserveEmptyRows($this->preserveEmptyRows);
    }

    /**
     * @inheritdoc
     */
    public function getSheets(ReaderInterface $reader): iterable
    {
        return $reader->getSheetIterator();
    }
}
