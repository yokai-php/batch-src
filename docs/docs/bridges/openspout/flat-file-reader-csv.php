<?php

declare(strict_types=1);

use OpenSpout\Reader\CSV\Options as CSVOptions;
use Yokai\Batch\Bridge\OpenSpout\Reader\FlatFileReader;
use Yokai\Batch\Bridge\OpenSpout\Reader\HeaderStrategy;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;

// Read .csv file
// The CSV delimiter and enclosure has been changed from default (respectively ',' & '"')
// Each lines will be read as simple array
$options = new CSVOptions();
$options->FIELD_DELIMITER = ';';
$options->FIELD_ENCLOSURE = '|';
new FlatFileReader(
    filePath: new StaticValueParameterAccessor('/path/to/file.csv'),
    options: $options,
    headerStrategy: HeaderStrategy::none(),
);
