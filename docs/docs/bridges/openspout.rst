Bridge with ``openspout/openspout``
============================================================

| The ``OpenSpout`` library allows to read and write with the same API from CSV/ODS/XLSX files.
| The bridge will allow you to do the same, within an ``ItemJob``.

Item reader
------------------------------------------------------------

The `FlatFileReader <https://github.com/yokai-php/batch-openspout/blob/0.x/src/Reader/FlatFileReader.php>`__ is a reader
that will read from CSV/ODS/XLSX file and return each line as an array.

.. code-block:: php

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

.. seealso::
   | :doc:`What is an item reader? </core-concepts/item-job/item-reader>`

Item writer
------------------------------------------------------------

The `FlatFileWriter <https://github.com/yokai-php/batch-openspout/blob/0.x/src/Writer/FlatFileWriter.php>`__ is a writer
that will write to CSV/ODS/XLSX file and each item will written its own line.

.. code-block:: php

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
    // That file will contain a header line with: static | header | keys
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

.. seealso::
   | :doc:`What is an item writer? </core-concepts/item-job/item-writer>`
