# Item reader with CSV/ODS/XLSX files

The [FlatFileReader](../src/Reader/FlatFileReader.php) is a reader 
that will read from CSV/ODS/XLSX file and return each line as an array.

```php
<?php

use OpenSpout\Reader\CSV\Options as CSVOptions;
use OpenSpout\Reader\ODS\Options as ODSOptions;
use OpenSpout\Reader\XLSX\Options as XLSXOptions;
use Yokai\Batch\Bridge\OpenSpout\Reader\FlatFileReader;
use Yokai\Batch\Bridge\OpenSpout\Reader\HeaderStrategy;
use Yokai\Batch\Bridge\OpenSpout\Reader\SheetFilter;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;

// Read .xlsx file
// Every sheet will be read
// All lines will be read as simple array
new FlatFileReader(new StaticValueParameterAccessor('/path/to/file.xlsx'));

// Read .csv file
// The CSV delimiter and enclosure has been changed from default (respectively ',' & '"')
// Each lines will be read as simple array
$options = new CSVOptions();
$options->FIELD_DELIMITER = ';';
$options->FIELD_ENCLOSURE = '|';
new FlatFileReader(
    new StaticValueParameterAccessor('/path/to/file.csv'),
    $options,
    null,
    HeaderStrategy::none(),
);

// Read .ods file
// Only sheet named "Sheet name to read" will be read
// Each item will be an array_combine of first line as key and line as values
new FlatFileReader(
    new StaticValueParameterAccessor('/path/to/file.ods'),
    null,
    SheetFilter::nameIs('Sheet name to read'),
    HeaderStrategy::combine(),
);
```

## On the same subject

- [What is an item reader ?](https://github.com/yokai-php/batch/blob/0.x/docs/domain/item-job/item-reader.md)
