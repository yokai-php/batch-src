Item Job
============================================================

What is an item?
------------------------------------------------------------

The library allows you to declare and execute jobs, but wait why do we
named it batch then? There you are, the ``ItemJob`` is where batch
processing actually starts.

This is just a job that has been prepared to batch handle items.

If you are familiar with the concept of an `ETL <https://en.wikipedia.org/wiki/Extract,_transform,_load>`__,
this is pretty much the same.

How to create an item job?
------------------------------------------------------------

The item job allows you to split your logic into 3 different component:

* an :doc:`item reader </core-concepts/item-job/item-reader>`: stands for **Extract** in ETL
* an :doc:`item processor </core-concepts/item-job/item-processor>`: stands for **Transform** in ETL
* an :doc:`item writer </core-concepts/item-job/item-writer>`: stands for **Load** in ETL

.. code-block:: php

    <?php

    use Yokai\Batch\Job\Item\ItemJob;
    use Yokai\Batch\Job\Item\ItemProcessorInterface;
    use Yokai\Batch\Job\Item\ItemReaderInterface;
    use Yokai\Batch\Job\Item\ItemWriterInterface;
    use Yokai\Batch\Storage\NullJobExecutionStorage;

    class ItemReader implements ItemReaderInterface
    {
        public function read(): iterable
        {
            yield '1';
            yield '2';
            yield '3';
        }
    }

    class ItemProcessor implements ItemProcessorInterface
    {
        public function process($item)
        {
            return intval($item);
        }
    }

    class ItemWriter implements ItemWriterInterface
    {
        public function write(iterable $items): void
        {
            file_put_contents(__DIR__ . '/file.log', 'write :' . PHP_EOL, FILE_APPEND);
            foreach ($items as $item) {
                file_put_contents(__DIR__ . '/file.log', print_r($item, true) . PHP_EOL, FILE_APPEND);
            }
        }
    }

    $job = new ItemJob(2, new ItemReader(), new ItemProcessor(), new ItemWriter(), new NullJobExecutionStorage());

.. seealso::
   | :doc:`What is a job? </core-concepts/job>`
   | :doc:`What is a job launcher? </core-concepts/job-launcher>`
