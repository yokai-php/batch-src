# Item reader with CSV/ODS/XLSX files

The [FlatFileReader](../src/FlatFileReader.php) is a reader 
that will read from CSV/ODS/XLSX file and return each line as an array.

```php
<?php

use Box\Spout\Common\Type;
use Yokai\Batch\Bridge\Box\Spout\FlatFileReader;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;

// Read .xlsx file
// Each item will be an array_combine of first line as key and line as values
new FlatFileReader(Type::XLSX, new StaticValueParameterAccessor('/path/to/file.xlsx'));

// Read .csv file
// Each item will be an array_combine of first line as key and line as values
// The CSV delimiter and enclosure has been changed from default (respectively ',' & '"')
new FlatFileReader(Type::CSV, new StaticValueParameterAccessor('/path/to/file.csv'), ['delimiter' => ';', 'enclosure' => '|']);

// Read .ods file
// Each item will be an array_combine of headers constructor arg as key and line as values
new FlatFileReader(Type::ODS, new StaticValueParameterAccessor('/path/to/file.ods'), [], FlatFileReader::HEADERS_MODE_SKIP, ['static', 'header', 'keys']);
```

## On the same subject

- [What is an item reader ?](https://github.com/yokai-php/batch/blob/0.x/docs/domain/item-job/item-reader.md)
