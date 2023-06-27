# Item writer with CSV/ODS/XLSX files

The [FlatFileWriter](../src/Writer/FlatFileWriter.php) is a writer that will write to CSV/ODS/XLSX file and each item will
written its own line.

```php
<?php

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Options as CSVOptions;
use OpenSpout\Writer\ODS\Options as ODSOptions;
use OpenSpout\Writer\XLSX\Options as XLSXOptions;
use Yokai\Batch\Bridge\OpenSpout\Writer\FlatFileWriter;
use Yokai\Batch\Bridge\OpenSpout\Writer\Options\CSVOptions;
use Yokai\Batch\Bridge\OpenSpout\Writer\Options\ODSOptions;
use Yokai\Batch\Bridge\OpenSpout\Writer\Options\XLSXOptions;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;

// Write items to .xlsx file
// That file will not contain a header line
new FlatFileWriter(new StaticValueParameterAccessor('/path/to/file.xlsx'));

// Write items to .csv file
// That file will not contain a header line
// The CSV delimiter and enclosure has been changed from default (respectively ',' & '"')
$options = new CSVOptions();
$options->FIELD_DELIMITER = ';';
$options->FIELD_ENCLOSURE = '|';
new FlatFileWriter(
    new StaticValueParameterAccessor('/path/to/file.csv'),
    $options,
);

// Write items to .ods file
// That file will contain a header line with : static | header | keys
// Change the sheet name data will be written
// Change the default style of each cell
$options = new ODSOptions();
$options->DEFAULT_ROW_STYLE = (new Style())->setFontBold();
new FlatFileWriter(
    new StaticValueParameterAccessor('/path/to/file.ods'),
    $options,
    'The sheet name',
    ['static', 'header', 'keys'],
);
```

## On the same subject

- [What is an item writer ?](https://github.com/yokai-php/batch/blob/0.x/docs/domain/item-job/item-writer.md)
