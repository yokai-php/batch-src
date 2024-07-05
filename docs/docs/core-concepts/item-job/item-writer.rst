What is an item writer?
============================================================

The item writer is used by the item job to load every processed item.

It can be any class implementing
`ItemWriterInterface <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/ItemWriterInterface.php>`__.

What types of item writers exists?
------------------------------------------------------------

**Built-in item writers:** 

* `JsonLinesWriter <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Writer/Filesystem/JsonLinesWriter.php>`__:
  write items as a json string each on a line of a file.
* `ChainWriter <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Writer/ChainWriter.php>`__:
  write items on multiple item writers.
* `ConditionalWriter <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Writer/ConditionalWriter.php>`__:
  will only write items that are matching your conditions.
* `DispatchEventsWriter <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Writer/DispatchEventsWriter.php>`__:
  will dispatch events before and after writing.
* `LaunchJobForEachItemWriter <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Writer/LaunchJobForEachItemWriter.php>`__:
  launch another job for each items.
* `LaunchJobForItemsBatchWriter <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Writer/LaunchJobForItemsBatchWriter.php>`__:
  launch another job for each item batches.
* `NullWriter <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Writer/NullWriter.php>`__:
  do not write items.
* `RoutingWriter <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Writer/RoutingWriter.php>`__:
  route writing to different writer based on your logic.
* `SummaryWriter <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Writer/SummaryWriter.php>`__:
  write items to a job summary value.
* `TransformingWriter <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Writer/TransformingWriter.php>`__:
  perform items transformation before delegating to another writer.
* `CallbackWriter <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Writer/CallbackWriter.php>`__:
  delegate items write operations to a closure passed at construction.

**Item writers from bridges:**

* From ``symfony/messenger`` bridge:

  * `DispatchEachItemAsMessageWriter <https://github.com/yokai-php/batch-symfony-messenger/blob/0.x/src/src/Writer/DispatchEachItemAsMessageWriter.php>`__:
    dispatch each item as a message in a bus.

* From ``doctrine/dbal`` bridge:

  * `DoctrineDBALInsertWriter <https://github.com/yokai-php/batch-doctrine-dbal/blob/0.x/src/src/DoctrineDBALInsertWriter.php>`__:
    write items by inserting in a table via a Doctrine ``Connection``.
  * `DoctrineDBALUpsertWriter <https://github.com/yokai-php/batch-doctrine-dbal/blob/0.x/src/src/DoctrineDBALUpsertWriter.php>`__:
    write items by inserting/updating in a table via a Doctrine ``Connection``.

* From ``doctrine/persistence`` bridge:

  * `ObjectWriter <https://github.com/yokai-php/batch-doctrine-persistence/blob/0.x/src/src/ObjectWriter.php>`__:
    write items to any Doctrine ``ObjectManager``.

* From ``openspout/openspout`` bridge:

  * `FlatFileWriter <https://github.com/yokai-php/batch-openspout/blob/0.x/src/src/Writer/FlatFileWriter.php>`__:
    write items to any CSV/ODS/XLSX file.

**Item writers for testing purpose:**

* `InMemoryWriter <https://github.com/yokai-php/batch/blob/0.x/src/Test/Job/Item/Writer/InMemoryWriter.php>`__:
  write in a private var which can be accessed afterward in your tests.
* `TestDebugWriter <https://github.com/yokai-php/batch/blob/0.x/src/Test/Job/Item/Writer/TestDebugWriter.php>`__:
  dummy item writer that you can use in your unit tests.

.. seealso::
   | :doc:`What is an item job? </core-concepts/item-job>`
