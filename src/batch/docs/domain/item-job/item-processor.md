# What is an item processor ?

The item processor is used by the item job to transform every read item.

It can be any class implementing [ItemProcessorInterface](../../../src/Job/Item/ItemProcessorInterface.php).

## What types of item processors exists ?

**Built-in item processors:**
- [ArrayMapProcessor](../../../src/Job/Item/Processor/ArrayMapProcessor.php):
  apply a callback to each element of array items.
- [CallbackProcessor](../../../src/Job/Item/Processor/CallbackProcessor.php):
  use a callback to transform each items.
- [ChainProcessor](../../../src/Job/Item/Processor/ChainProcessor.php):
  chain transformation of multiple item processor, one after the other.
- [FilterUniqueProcessor](../../../src/Job/Item/Processor/FilterUniqueProcessor.php):
  assign an identifier to each item, and skip already encountered items.
- [NullProcessor](../../../src/Job/Item/Processor/NullProcessor.php):
  perform no transformation on items.
- [RoutingProcessor](../../../src/Job/Item/Processor/RoutingProcessor.php):
  route processing to different processor based on your logic.

**Item processors from bridges:**
- [SkipInvalidItemProcessor (`symfony/validator`)](https://github.com/yokai-php/batch-symfony-validator/blob/0.x/src/SkipInvalidItemProcessor.php):
  validate item and throw exception if invalid that will cause item to be skipped.
- [DenormalizeItemProcessor (`symfony/serializer`)](https://github.com/yokai-php/batch-symfony-serializer/blob/0.x/src/DenormalizeItemProcessor.php):
  denormalize each item.
- [NormalizeItemProcessor (`symfony/serializer`)](https://github.com/yokai-php/batch-symfony-serializer/blob/0.x/src/NormalizeItemProcessor.php):
  normalize each item.

**Item processors for testing purpose:**
- [TestDebugProcessor](../../../src/Test/Job/Item/Processor/TestDebugProcessor.php):
  dummy item processor that you can use in your unit tests.

## On the same subject

- [What is an item job ?](../item-job.md)
