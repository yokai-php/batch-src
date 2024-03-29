# What is an item reader ?

The item reader is used by the item job to extract item from a source.

It can be any class implementing [ItemReaderInterface](../../../src/Job/Item/ItemReaderInterface.php).

## What types of item readers exists ?

**Built-in item readers:**
- [FixedColumnSizeFileReader](../../../src/Job/Item/Reader/Filesystem/FixedColumnSizeFileReader.php):
  read a file line by line, and decode each line with fixed columns size to an array.
- [JsonLinesReader](../../../src/Job/Item/Reader/Filesystem/JsonLinesReader.php):
  read a file line by line, and decode each line as JSON.
- [AddMetadataReader](../../../src/Job/Item/Reader/AddMetadataReader.php):
  decorates another reader by adding static information to each read item.
- [IndexWithReader](../../../src/Job/Item/Reader/IndexWithReader.php):
  decorates another reader by changing index of each item.
- [ParameterAccessorReader](../../../src/Job/Item/Reader/ParameterAccessorReader.php):
  read from an inmemory value located at some configurable place.
- [SequenceReader](../../../src/Job/Item/Reader/SequenceReader.php):
  read from multiple item reader, one after the other.
- [StaticIterableReader](../../../src/Job/Item/Reader/StaticIterableReader.php):
  read from an iterable you provide during construction.

**Item readers from bridges:**
- [FlatFileReader (`openspout/openspout`)](https://github.com/yokai-php/batch-openspout/blob/0.x/src/Reader/FlatFileReader.php):
  read from any CSV/ODS/XLSX file.
- [DoctrineDBALQueryOffsetReader (`doctrine/dbal`)](https://github.com/yokai-php/batch-doctrine-dbal/blob/0.x/src/DoctrineDBALQueryOffsetReader.php):
  read execute an SQL query and iterate over results, using a limit + offset pagination strategy.
- [DoctrineDBALQueryCursorReader (`doctrine/dbal`)](https://github.com/yokai-php/batch-doctrine-dbal/blob/0.x/src/DoctrineDBALQueryCursorReader.php):
  read execute an SQL query and iterate over results, using a column based cursor strategy.
- [EntityReader (`doctrine/orm`)](https://github.com/yokai-php/batch-doctrine-orm/blob/0.x/src/EntityReader.php):
  read from any Doctrine ORM entity.

**Item readers for testing purpose:**
- [TestDebugReader](../../../src/Test/Job/Item/Reader/TestDebugReader.php):
  dummy item reader that you can use in your unit tests.

## On the same subject

- [What is an item job ?](../item-job.md)
