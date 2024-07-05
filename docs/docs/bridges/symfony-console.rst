Bridge with ``symfony/console``
============================================================

todo

Command
------------------------------------------------------------

The `RunJobCommand <https://github.com/yokai-php/batch-symfony-console/blob/0.x/src/src/RunJobCommand.php>`__
can execute any job.

The command accepts 2 arguments:

* the job name to execute
* the job parameters for the ``JobExecution`` (optional)

Examples:

.. code-block:: console

    bin/console import
    bin/console export '{"toFile":"/path/to/file.xml"}'

Job launcher
------------------------------------------------------------

The `RunCommandJobLauncher <https://github.com/yokai-php/batch-symfony-console/blob/0.x/src/src/RunCommandJobLauncher.php>`__
execute jobs via an asynchronous symfony command.

The command called is ``yokai:batch:run``, and the command will actually execute the job.

Additionally, the command will run with an output redirect (``>>``) to ``var/log/batch_execute.log``.

.. seealso::
   | :doc:`What is a job launcher? </core-concepts/job-launcher>`
