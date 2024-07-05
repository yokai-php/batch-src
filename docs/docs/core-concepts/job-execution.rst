Job execution
============================================================

What is a Job execution?
------------------------------------------------------------

A `JobExecution <https://github.com/yokai-php/batch/tree/0.x/src/JobExecution.php>`__ is the class that holds
information about one execution of a :doc:`job </core-concepts/job>`.

What kind of information does it hold?
------------------------------------------------------------

* ``JobExecution::$jobName``: The Job name (job id)
* ``JobExecution::$id``: The execution id
* ``JobExecution::$parameters``: Some parameters with which job was executed
* ``JobExecution::$status``: A status (pending, running, stopped, completed, abandoned, failed)
* ``JobExecution::$startTime``: Start time
* ``JobExecution::$endTime``: End time
* ``JobExecution::$failures``: A list of failures (usually exceptions)
* ``JobExecution::$warnings``: A list of warnings (usually skipped items)
* ``JobExecution::$summary``: A summary (can contain any data you wish to store)
* ``JobExecution::$logs``: Some logs
* ``JobExecution::$childExecutions``: Some child execution

.. seealso::
   | :doc:`How is a job execution created? </core-concepts/job-launcher>`
   | :doc:`How can I retrieve a job execution afterwards? </core-concepts/job-execution-storage>`
