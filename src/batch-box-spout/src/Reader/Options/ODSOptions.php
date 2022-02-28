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
    private bool $formatDates;
    private bool $preserveEmptyRows;

    public function __construct(
        SheetFilter $sheetFilter = null,
        bool $formatDates = false,
        bool $preserveEmptyRows = false
    ) {
        $this->sheetFilter = $sheetFilter ?? SheetFilter::all();
        $this->formatDates = $formatDates;
        $this->preserveEmptyRows = $preserveEmptyRows;
    }

    /**
     * @inheritDoc
     */
    public function configure(ReaderInterface $reader): void
    {
        if (!$reader instanceof ODSReader) {
            throw UnexpectedValueException::type(ODSReader::class, $reader);
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
