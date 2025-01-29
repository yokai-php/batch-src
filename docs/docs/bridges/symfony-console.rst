Bridge with ``symfony/console``
============================================================

Refer to the `official documentation <https://symfony.com/doc/current/messenger.html>`__ on Symfony's website.

This bridge provides ways to interact with the library within a CLI command.


Launch Job command
------------------------------------------------------------

The `RunJobCommand <https://github.com/yokai-php/batch-symfony-console/blob/0.x/src/RunJobCommand.php>`__
can execute any job.

The command accepts 2 arguments:

* the job name to execute
* the job parameters for the ``JobExecution`` (optional)

Examples:

.. code-block:: console

    bin/console yokai:batch:run import
    bin/console yokai:batch:run export '{"toFile":"/path/to/file.xml"}'

.. seealso::
   | :doc:`What is an job? </core-concepts/job>`


Launch job with an asynchronous command
------------------------------------------------------------

The `RunCommandJobLauncher <https://github.com/yokai-php/batch-symfony-console/blob/0.x/src/RunCommandJobLauncher.php>`__
execute jobs via an asynchronous symfony command.

The command called is ``yokai:batch:run``, and the command will actually execute the job.

Additionally, the command will run with an output redirect (``>>``) to ``var/log/batch_execute.log``.

.. seealso::
   | :doc:`What is a job launcher? </core-concepts/job-launcher>`


Setup JobExecution storage command
------------------------------------------------------------

The `SetupStorageCommand <https://github.com/yokai-php/batch-symfony-console/blob/0.x/src/SetupStorageCommand.php>`__
can prepare required infrastructure for the configured ``JobExecution`` storage.

Usage:

.. code-block:: console

    bin/console yokai:batch:setup-storage

.. seealso::
   | :doc:`What is a job execution storage? </core-concepts/job-execution-storage>`
