<?php

declare(strict_types=1);

use Yokai\Batch\Bridge\OpenSpout\Reader\FlatFileReader;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;

// Read .xlsx file
// Every sheet will be read
// All lines will be read as simple array
new FlatFileReader(
    filePath: new StaticValueParameterAccessor('/path/to/file.xlsx'),
);
