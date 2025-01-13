<?php

declare(strict_types=1);

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\ODS\Options as ODSOptions;
use Yokai\Batch\Bridge\OpenSpout\Writer\FlatFileWriter;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;

// Write items to .ods file
// That file will contain a header line with: static | header | keys
// Change the sheet name data will be written
// Change the default style of each cell
$options = new ODSOptions();
$options->DEFAULT_ROW_STYLE = (new Style())->setFontBold();
new FlatFileWriter(
    filePath: new StaticValueParameterAccessor('/path/to/file.ods'),
    options: $options,
    defaultSheet: 'The sheet name',
    headers: ['static', 'header', 'keys'],
);
