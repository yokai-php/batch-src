<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Reader\Options;

use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\XLSX\Reader as XLSXReader;
use Yokai\Batch\Exception\UnexpectedValueException;

/**
 * Options for reading XLSX files.
 */
final class XLSXOptions implements OptionsInterface
{
    private SheetFilter $sheetFilter;

    public function __construct(
        SheetFilter $sheetFilter = null,
        private bool $formatDates = false,
        private bool $preserveEmptyRows = false,
    ) {
        $this->sheetFilter = $sheetFilter ?? SheetFilter::all();
    }

    /**
     * @inheritDoc
     */
    public function configure(ReaderInterface $reader): void
    {
        if (!$reader instanceof XLSXReader) {
            throw UnexpectedValueException::type(XLSXReader::class, $reader);
        }

        $reader->setShouldFormatDates($this->formatDates);
        $reader->setShouldPreserveEmptyRows($this->preserveEmptyRows);
    }

    /**
     * @inheritDoc
     */
    public function getSheets(ReaderInterface $reader): iterable
    {
        yield from $this->sheetFilter->getSheets($reader);
    }
}
