Job execution logger
============================================================

What is a job execution logger?
------------------------------------------------------------

Every ``JobExecution`` carries a
`JobExecutionLoggerInterface <https://github.com/yokai-php/batch/tree/1.x/src/Logger/JobExecutionLoggerInterface.php>`__
instance that records log messages produced during the execution.

It extends PSR-3's ``LoggerInterface``, so anything you can do with a standard logger
(``info``, ``warning``, ``error``, etc.) works here.
On top of that, it exposes two extra methods to read the logs back:

* ``getLogs(): iterable<string>`` — streams log lines lazily, suitable for large files or HTTP streaming
* ``getLogsContent(): string`` — returns the full log content as a string (loads everything in memory)

It also exposes ``getReference(): string``, a string serialized alongside the ``JobExecution``
that allows the log storage to be restored after the execution is loaded back from storage.

How are loggers created and restored?
------------------------------------------------------------

The
`JobExecutionLoggerFactoryInterface <https://github.com/yokai-php/batch/tree/1.x/src/Factory/JobExecutionLoggerFactoryInterface.php>`__
is responsible for the full lifecycle:

* ``create(string $jobExecutionId)`` — called when a new ``JobExecution`` is created.
  Returns a fresh logger ready to receive messages.
* ``restore(string $logsReference)`` — called when a ``JobExecution`` is loaded from storage.
  Reconstructs the logger from the reference that was serialized with the execution.

You should never have to call these methods yourself; the framework handles it internally.

What implementations exist?
------------------------------------------------------------

**Built-in implementations:**

* `InMemoryJobExecutionLoggerFactory <https://github.com/yokai-php/batch/tree/1.x/src/Factory/JobExecutionLoggerFactory/InMemoryJobExecutionLoggerFactory.php>`__
  (default): keeps logs in memory. Logs are lost when the process ends.
* `NullJobExecutionLoggerFactory <https://github.com/yokai-php/batch/tree/1.x/src/Factory/JobExecutionLoggerFactory/NullJobExecutionLoggerFactory.php>`__:
  discards all log messages.

**From bridges:**

* From ``monolog/monolog`` bridge:

  * `StreamJobExecutionLoggerFactory <https://github.com/yokai-php/batch-monolog/blob/1.x/src/StreamJobExecutionLoggerFactory.php>`__:
    writes one log file per job execution using Monolog's ``StreamHandler``.
    Logs survive the process and can be read back from the file.

.. seealso::
   | :doc:`What is a job execution? </core-concepts/job-execution>`
   | :doc:`Bridge with Monolog </bridges/monolog>`
   | :doc:`Bridge with Symfony Framework </bridges/symfony-framework>`
