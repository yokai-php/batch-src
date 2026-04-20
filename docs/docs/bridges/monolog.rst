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
  similarly to how Git stores objects:

.. literalinclude:: monolog/stream-logger-factory-with-subdirectories.php
   :language: php


Customize the Monolog stack
------------------------------------------------------------

| You can provide Monolog processors and a custom formatter to control how records are written.
| Processors are applied to every log record before it is written.
| The formatter controls the final string representation written to the file.

.. literalinclude:: monolog/stream-logger-factory-with-processors.php
   :language: php
