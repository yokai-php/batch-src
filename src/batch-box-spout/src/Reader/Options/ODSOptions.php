<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Reader\Options;

use Box\Spout\Reader\ODS\Reader as ODSReader;
use Box\Spout\Reader\ReaderInterface;
use Yokai\Batch\Exception\UnexpectedValueException;

/**
 * Options for reading ODS files.
 */
final class ODSOptions implements OptionsInterface
{
    private SheetFilter $sheetFilter;

    public function __construct(
        SheetFilter $sheetFilter = null,
        private bool $formatDates = false,
        private bool $preserveEmptyRows = false,
    ) {
        $this->sheetFilter = $sheetFilter ?? SheetFilter::all();
    }

    public function configure(ReaderInterface $reader): void
    {
        if (!$reader instanceof ODSReader) {
            throw UnexpectedValueException::type(ODSReader::class, $reader);
        }

        $reader->setShouldFormatDates($this->formatDates);
        $reader->setShouldPreserveEmptyRows($this->preserveEmptyRows);
    }

    public function getSheets(ReaderInterface $reader): iterable
    {
        yield from $this->sheetFilter->getSheets($reader);
    }
}
