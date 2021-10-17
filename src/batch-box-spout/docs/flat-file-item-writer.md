# Item writer with CSV/ODS/XLSX files

The [FlatFileWriter](../src/Writer/FlatFileWriter.php) is a writer that will write to CSV/ODS/XLSX file and each item will
written its own line.

```php
<?php

use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Yokai\Batch\Bridge\Box\Spout\Writer\FlatFileWriter;
use Yokai\Batch\Bridge\Box\Spout\Writer\Options\CSVOptions;
use Yokai\Batch\Bridge\Box\Spout\Writer\Options\ODSOptions;
use Yokai\Batch\Bridge\Box\Spout\Writer\Options\XLSXOptions;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;

// Write items to .xlsx file
// That file will not contain a header line
new FlatFileWriter(new StaticValueParameterAccessor('/path/to/file.xlsx'), new XLSXOptions());

// Write items to .csv file
// That file will not contain a header line
// The CSV delimiter and enclosure has been changed from default (respectively ',' & '"')
new FlatFileWriter(
    new StaticValueParameterAccessor('/path/to/file.csv'),
    new CSVOptions(';', '|')
);

// Write items to .ods file
// That file will contain a header line with : static | header | keys
// Change the sheet name data will be written
// Change the default style of each cell
new FlatFileWriter(
    new StaticValueParameterAccessor('/path/to/file.ods'),
    new ODSOptions('The sheet name', (new StyleBuilder())->setFontBold()->build()),
    ['static', 'header', 'keys']
);
```

## On the same subject

- [What is an item writer ?](https://github.com/yokai-php/batch/blob/0.x/docs/domain/item-job/item-writer.md)
