<?php

declare(strict_types=1);

use Yokai\Batch\Bridge\OpenSpout\Writer\FlatFileWriter;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;

// Write items to .xlsx file
// That file will not contain a header line
new FlatFileWriter(
    filePath: new StaticValueParameterAccessor('/path/to/file.xlsx'),
);
