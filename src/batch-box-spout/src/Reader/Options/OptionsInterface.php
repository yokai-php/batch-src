<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Reader\Options;

use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\SheetInterface;

/**
 * Options for reading flat files with box/spout.
 * Use proper implementation associated with your file type :
 * - {@see CSVOptions} for CSV files
 * - {@see ODSOptions} for ODS files
 * - {@see XLSXOptions} for XLSX files
 *
 * @internal
 */
interface OptionsInterface
{
    /**
     * Configure box/spout reader before reading.
     * @internal
     */
    public function configure(ReaderInterface $reader): void;

    /**
     * Extract the list of readable sheets from box/spout reader.
     *
     * @return iterable&SheetInterface[]
     * @phpstan-return iterable<SheetInterface>
     * @internal
     */
    public function getSheets(ReaderInterface $reader): iterable;
}
