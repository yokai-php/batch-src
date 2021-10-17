# Item reader with CSV/ODS/XLSX files

The [FlatFileReader](../src/Reader/FlatFileReader.php) is a reader 
that will read from CSV/ODS/XLSX file and return each line as an array.

```php
<?php

use Yokai\Batch\Bridge\Box\Spout\Reader\FlatFileReader;
use Yokai\Batch\Bridge\Box\Spout\Reader\HeaderStrategy;
use Yokai\Batch\Bridge\Box\Spout\Reader\Options\CSVOptions;
use Yokai\Batch\Bridge\Box\Spout\Reader\Options\ODSOptions;
use Yokai\Batch\Bridge\Box\Spout\Reader\Options\SheetFilter;
use Yokai\Batch\Bridge\Box\Spout\Reader\Options\XLSXOptions;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;

// Read .xlsx file
// Every sheet will be read
// First line of each sheet will be skipped
// Other lines will be read as simple array
new FlatFileReader(new StaticValueParameterAccessor('/path/to/file.xlsx'), new XLSXOptions());

// Read .csv file
// The CSV delimiter and enclosure has been changed from default (respectively ',' & '"')
// Each lines will be read as simple array
new FlatFileReader(
    new StaticValueParameterAccessor('/path/to/file.csv'),
    new CSVOptions(';', '|'),
    HeaderStrategy::none()
);

// Read .ods file
// Only sheet named "Sheet name to read" will be read
// Each item will be an array_combine of first line as key and line as values
new FlatFileReader(
    new StaticValueParameterAccessor('/path/to/file.ods'),
    new ODSOptions(SheetFilter::nameIs('Sheet name to read')),
    HeaderStrategy::combine()
);
```

## On the same subject

- [What is an item reader ?](https://github.com/yokai-php/batch/blob/0.x/docs/domain/item-job/item-reader.md)
