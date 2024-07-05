\*Aware interfaces
============================================================

| When a job execution starts,
  a `JobExecution <https://github.com/yokai-php/batch/blob/0.x/src/JobExecution.php>`__ is created for it.
| This object contains information about the current execution.

You will often want to access this object or one of its child to :

* access provided user parameters in your components
* leave some information on the job execution: logs, summary, warning...

To do that, your component will need to implement an interface, telling the library that you need something.

What is ``JobExecutionAwareInterface``?
------------------------------------------------------------

The `JobExecutionAwareInterface <https://github.com/yokai-php/batch/blob/0.x/src/Job/JobExecutionAwareInterface.php>`__
will allow you to gain access to the current
`JobExecution <https://github.com/yokai-php/batch/blob/0.x/src/JobExecution.php>`__.

.. note::
   This interface is covered by
   `JobExecutionAwareTrait <https://github.com/yokai-php/batch/blob/0.x/src/Job/JobExecutionAwareTrait.php>`__
   for a default implementation that is most of the time sufficient.

What is ``JobParametersAwareInterface``?
------------------------------------------------------------

The
`JobParametersAwareInterface <https://github.com/yokai-php/batch/blob/0.x/src/Job/JobParametersAwareInterface.php>`__
will allow you to gain access to the
`JobParameters <https://github.com/yokai-php/batch/blob/0.x/src/JobParameters.php>`__ of the current execution.

.. note::
   This interface is covered by
   `JobParametersAwareTrait <https://github.com/yokai-php/batch/blob/0.x/src/Job/JobParametersAwareTrait.php>`__
   for a default implementation that is most of the time sufficient.

What is ``SummaryAwareInterface``?
------------------------------------------------------------

The `SummaryAwareInterface <https://github.com/yokai-php/batch/blob/0.x/src/Job/SummaryAwareInterface.php>`__
will allow you to gain access to the `Summary <https://github.com/yokai-php/batch/blob/0.x/src/Summary.php>`__ of the current execution.

.. note::
   This interface is covered by    `SummaryAwareTrait <https://github.com/yokai-php/batch/blob/0.x/src/Job/SummaryAwareTrait.php>`__
   for a default implementation that is most of the time sufficient.

How does that work exactly?
------------------------------------------------------------

There is no magic involved here, every component is responsible for propagating the context through these interfaces.

In the library, you will find that :

* `ItemJob <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/ItemJob.php>`__ is propagating context to
  `ItemReaderInterface <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/ItemReaderInterface.php>`__,
  `ItemProcessorInterface <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/ItemProcessorInterface.php>`__
  and
  `ItemWriterInterface <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/ItemWriterInterface.php>`__.
* Every
  `ItemReaderInterface <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/ItemReaderInterface.php>`__,
  `ItemProcessorInterface <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/ItemProcessorInterface.php>`__
  and
  `ItemWriterInterface <https://github.com/yokai-php/batch/blob/0.x/src/Job/Item/ItemWriterInterface.php>`__
  acting as a decorator, is propagating context to their decorated element.

You can add this interface to any class, but you are responsible for the context propagation.

.. seealso::
   | :doc:`What is an item reader? </core-concepts/item-job/item-reader>`
