# Item writer with CSV/ODS/XLSX files

The [FlatFileWriter](../src/FlatFileWriter.php) is a writer that will write to CSV/ODS/XLSX file and each item will
written its own line.

```php
<?php

use Box\Spout\Common\Type;
use Yokai\Batch\Bridge\Box\Spout\FlatFileWriter;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;

// Write items to .xlsx file
// That File will not contain a header line
new FlatFileWriter(Type::XLSX, new StaticValueParameterAccessor('/path/to/file.xlsx'));

// Write items to .csv file
// That File will not contain a header line
// The CSV delimiter and enclosure has been changed from default (respectively ',' & '"')
new FlatFileWriter(Type::CSV, new StaticValueParameterAccessor('/path/to/file.csv'), [], ['delimiter' => ';', 'enclosure' => '|']);

// Write items to .ods file
// That File will contain a header line with : static | header | keys
new FlatFileWriter(Type::ODS, new StaticValueParameterAccessor('/path/to/file.ods'), ['static', 'header', 'keys']);
```

## On the same subject

- [What is an item writer ?](https://github.com/yokai-php/batch/blob/0.x/docs/domain/item-job/item-writer.md)
