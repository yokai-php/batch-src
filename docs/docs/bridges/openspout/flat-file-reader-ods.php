<?php

declare(strict_types=1);

use Yokai\Batch\Bridge\OpenSpout\Reader\FlatFileReader;
use Yokai\Batch\Bridge\OpenSpout\Reader\HeaderStrategy;
use Yokai\Batch\Bridge\OpenSpout\Reader\SheetFilter;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;

// Read .ods file
// Only sheet named "Sheet name to read" will be read
// Each item will be an array_combine of first line as key and line as values
new FlatFileReader(
    filePath: new StaticValueParameterAccessor('/path/to/file.ods'),
    sheetFilter: SheetFilter::nameIs('Sheet name to read'),
    headerStrategy: HeaderStrategy::combine(),
);
