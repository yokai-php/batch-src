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
    public function __construct(
        private string $delimiter = ',',
        private string $enclosure = '"',
        private string $encoding = 'UTF-8',
        private bool $formatDates = false,
        private bool $preserveEmptyRows = false,
    ) {
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
