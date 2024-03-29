# What is an item writer ?

The item writer is used by the item job to load every processed item.

It can be any class implementing [ItemWriterInterface](../../../src/Job/Item/ItemWriterInterface.php).

## What types of item writers exists ?

**Built-in item writers:**
- [JsonLinesWriter](../../../src/Job/Item/Writer/Filesystem/JsonLinesWriter.php):
  write items as a json string each on a line of a file.
- [ChainWriter](../../../src/Job/Item/Writer/ChainWriter.php):
  write items on multiple item writers.
- [ConditionalWriter](../../../src/Job/Item/Writer/ConditionalWriter.php):
  will only write items that are matching your conditions.
- [DispatchEventsWriter](../../../src/Job/Item/Writer/DispatchEventsWriter.php):
  will dispatch events before and after writing.
- [LaunchJobForEachItemWriter](../../../src/Job/Item/Writer/LaunchJobForEachItemWriter.php):
  launch another job for each items.
- [LaunchJobForItemsBatchWriter](../../../src/Job/Item/Writer/LaunchJobForItemsBatchWriter.php):
  launch another job for each item batches.
- [NullWriter](../../../src/Job/Item/Writer/NullWriter.php):
  do not write items.
- [RoutingWriter](../../../src/Job/Item/Writer/RoutingWriter.php):
  route writing to different writer based on your logic.
- [SummaryWriter](../../../src/Job/Item/Writer/SummaryWriter.php):
  write items to a job summary value.
- [TransformingWriter](../../../src/Job/Item/Writer/TransformingWriter.php):
  perform items transformation before delegating to another writer.

**Item writers from bridges:**
- [DispatchEachItemAsMessageWriter (`symfony/messenger`)](https://github.com/yokai-php/batch-symfony-messenger/blob/0.x/src/Writer/DispatchEachItemAsMessageWriter.php):
  dispatch each item as a message in a bus.
- [DoctrineDBALInsertWriter (`doctrine/dbal`)](https://github.com/yokai-php/batch-doctrine-dbal/blob/0.x/src/DoctrineDBALInsertWriter.php):
  write items by inserting in a table via a Doctrine `Connection`.
- [DoctrineDBALUpsertWriter (`doctrine/dbal`)](https://github.com/yokai-php/batch-doctrine-dbal/blob/0.x/src/DoctrineDBALUpsertWriter.php):
  write items by inserting/updating in a table via a Doctrine `Connection`.
- [ObjectWriter (`doctrine/persistence`)](https://github.com/yokai-php/batch-doctrine-persistence/blob/0.x/src/ObjectWriter.php):
  write items to any Doctrine `ObjectManager`.
- [FlatFileWriter (`openspout/openspout`)](https://github.com/yokai-php/batch-openspout/blob/0.x/src/Writer/FlatFileWriter.php):
  write items to any CSV/ODS/XLSX file.

**Item writers for testing purpose:**
- [InMemoryWriter](../../../src/Test/Job/Item/Writer/InMemoryWriter.php):
  write in a private var which can be accessed afterward in your tests.
- [TestDebugWriter](../../../src/Test/Job/Item/Writer/TestDebugWriter.php):
  dummy item writer that you can use in your unit tests.

## On the same subject

- [What is an item job ?](../item-job.md)
