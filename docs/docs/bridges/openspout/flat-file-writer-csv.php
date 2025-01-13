<?php

declare(strict_types=1);

use OpenSpout\Writer\CSV\Options as CSVOptions;
use Yokai\Batch\Bridge\OpenSpout\Writer\FlatFileWriter;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;

// Write items to .csv file
// That file will not contain a header line
// The CSV delimiter and enclosure has been changed from default (respectively ',' & '"')
$options = new CSVOptions();
$options->FIELD_DELIMITER = ';';
$options->FIELD_ENCLOSURE = '|';
new FlatFileWriter(
    filePath: new StaticValueParameterAccessor('/path/to/file.csv'),
    options: $options,
);
