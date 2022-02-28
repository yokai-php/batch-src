<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Writer\Options;

use Box\Spout\Writer\WriterInterface;

/**
 * Options for writing flat files with box/spout.
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
     * Configure box/spout writer before writing.
     * @internal
     */
    public function configure(WriterInterface $writer): void;
}
