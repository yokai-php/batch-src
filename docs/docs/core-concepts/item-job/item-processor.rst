What is an item processor?
============================================================

The item processor is used by the item job to transform every read item.

It can be any class implementing
`ItemProcessorInterface <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/ItemProcessorInterface.php>`__.

What types of item processors exists?
------------------------------------------------------------

**Built-in item processors:**

* `ArrayMapProcessor <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Processor/ArrayMapProcessor.php>`__:
  apply a callback to each element of array items.
* `CallbackProcessor <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Processor/CallbackProcessor.php>`__:
  use a callback to transform each items.
* `ChainProcessor <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Processor/ChainProcessor.php>`__:
  chain transformation of multiple item processor, one after the other.
* `FilterUniqueProcessor <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Processor/FilterUniqueProcessor.php>`__:
  assign an identifier to each item, and skip already encountered items.
* `NullProcessor <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Processor/NullProcessor.php>`__:
  perform no transformation on items.
* `RoutingProcessor <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/Processor/RoutingProcessor.php>`__:
  route processing to different processor based on your logic.

**Item processors from bridges:**

* From ``symfony/validator`` bridge:

  * `SkipInvalidItemProcessor <https://github.com/yokai-php/batch-symfony-validator/blob/0.x/src/src/SkipInvalidItemProcessor.php>`__:
    validate item and throw exception if invalid that will cause item to be skipped.

* From ``symfony/serializer`` bridge:

  * `DenormalizeItemProcessor <https://github.com/yokai-php/batch-symfony-serializer/blob/0.x/src/src/DenormalizeItemProcessor.php>`__:
    denormalize each item.
  * `NormalizeItemProcessor <https://github.com/yokai-php/batch-symfony-serializer/blob/0.x/src/src/NormalizeItemProcessor.php>`__:
    normalize each item.

**Item processors for testing purpose:**

* `TestDebugProcessor <https://github.com/yokai-php/batch/blob/0.x/src/Test/Job/Item/Processor/TestDebugProcessor.php>`__:
  dummy item processor that you can use in your unit tests.

.. seealso::
   | :doc:`What is an item job? </core-concepts/item-job>`
