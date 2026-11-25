Bridge with ``monolog/monolog``
============================================================

Refer to the `official documentation <https://seldaek.github.io/monolog/>`__ on Monolog's website.

This bridge provides file-based log storage for job executions using Monolog's ``StreamHandler``.


Store job execution logs in files
------------------------------------------------------------

| The
  `StreamJobExecutionLoggerFactory <https://github.com/yokai-php/batch-monolog/blob/1.x/src/StreamJobExecutionLoggerFactory.php>`__
  creates one log file per job execution, named after the job execution id.
| Logs written during execution are readable afterwards via the same reference.

.. warning::
   The directory must be shared by every process that launches, executes or displays job executions.
   Otherwise, logs of executions which ran elsewhere are read as empty.

.. note::
   | A job execution which logs were stored by another logger (the in-memory one, for instance)
     is restored with an in-memory logger, so its logs remain readable.
   | This happens with job executions stored before switching to this factory.

.. literalinclude:: monolog/stream-logger-factory.php
   :language: php

.. seealso::
   | :doc:`What is a job execution logger? </core-concepts/job-execution-logger>`
   | :doc:`Bridge with Symfony Framework </bridges/symfony-framework>`


Organize log files in subdirectories
------------------------------------------------------------

| By default, all log files land in the same directory.
| For large workloads this can become a filesystem performance issue.
| You can configure the factory to spread files across nested subdirectories,
  named after a hash of the job execution id, so files are spread evenly whatever the id generator:

.. literalinclude:: monolog/stream-logger-factory-with-subdirectories.php
   :language: php


Customize the Monolog stack
------------------------------------------------------------

| You can provide Monolog processors and a custom formatter to control how records are written.
| Processors are applied to every log record before it is written.
| The formatter controls the final string representation written to the file.

.. literalinclude:: monolog/stream-logger-factory-with-processors.php
   :language: php
