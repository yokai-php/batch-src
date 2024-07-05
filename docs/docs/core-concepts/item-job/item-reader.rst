What is an item reader?
============================================================

The item reader is used by the item job to extract item from a source.

It can be any class implementing
`ItemReaderInterface <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/ItemReaderInterface.php>`__.

What types of item readers exists?
------------------------------------------------------------

**Built-in item readers:**

* `FixedColumnSizeFileReader <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Reader/Filesystem/FixedColumnSizeFileReader.php>`__:
  read a file line by line, and decode each line with fixed columns size to an array.
* `JsonLinesReader <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Reader/Filesystem/JsonLinesReader.php>`__:
  read a file line by line, and decode each line as JSON.
* `AddMetadataReader <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Reader/AddMetadataReader.php>`__:
  decorates another reader by adding static information to each read item.
* `IndexWithReader <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Reader/IndexWithReader.php>`__:
  decorates another reader by changing index of each item.
* `ParameterAccessorReader <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Reader/ParameterAccessorReader.php>`__:
  read from an inmemory value located at some configurable place.
* `SequenceReader <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Reader/SequenceReader.php>`__:
  read from multiple item reader, one after the other.
* `StaticIterableReader <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Reader/StaticIterableReader.php>`__:
  read from an iterable you provide during construction.
* `CallbackReader <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Reader/CallbackReader.php>`__:
  read from a ``Closure`` you provide during construction.

**Item readers from bridges:**

* From ``openspout/openspout`` bridge:

  * `FlatFileReader <https://github.com/yokai-php/batch-openspout/blob/0.x/src/src/Reader/FlatFileReader.php>`__:
    read from any CSV/ODS/XLSX file.

* From ``doctrine/dbal`` bridge:

  * `DoctrineDBALQueryOffsetReader <https://github.com/yokai-php/batch-doctrine-dbal/blob/0.x/src/src/DoctrineDBALQueryOffsetReader.php>`__:
    execute an SQL query and iterate over results, using a limit + offset pagination strategy.
  * `DoctrineDBALQueryCursorReader <https://github.com/yokai-php/batch-doctrine-dbal/blob/0.x/src/src/DoctrineDBALQueryCursorReader.php>`__:
    execute an SQL query and iterate over results, using a column based cursor strategy.

* From ``doctrine/orm`` bridge:

  * `EntityReader <https://github.com/yokai-php/batch-doctrine-orm/blob/0.x/src/src/EntityReader.php>`__:
    read from any Doctrine ORM entity.

**Item readers for testing purpose:**

* `TestDebugReader <https://github.com/yokai-php/batch/blob/0.x/src/Test/Job/Item/Reader/TestDebugReader.php>`__:
  dummy item reader that you can use in your unit tests.

.. seealso::
   | :doc:`What is an item job? </core-concepts/item-job>`
