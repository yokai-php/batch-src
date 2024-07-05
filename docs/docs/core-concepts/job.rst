Job
============================================================

What is a job?
------------------------------------------------------------

A job is the class that is responsible for **what** your code is doing.

This is the class you will have to create (or reuse), as it contains the
business logic required for what you wish to achieve.

How to create a job?
------------------------------------------------------------

.. code-block:: php

    <?php

    use Yokai\Batch\JobExecution;
    use Yokai\Batch\Job\JobInterface;

    class DoStuffJob implements JobInterface
    {
        public function execute(JobExecution $jobExecution): void
        {
            // you stuff here
        }
    }

The only requirement is implementing
`JobInterface <https://github.com/yokai-php/batch/tree/0.x/src/Job/JobInterface.php>`__,

What types of job exists?
------------------------------------------------------------

**Built-in jobs:**

* `AbstractDecoratedJob <https://github.com/yokai-php/batch/tree/0.x/src/Job/AbstractDecoratedJob.php>`__: a job
  that is designed to be extended, helps job construction.
* `ItemJob <https://github.com/yokai-php/batch/tree/0.x/src/Job/Item/ItemJob.php>`__: ETL like, batch processing
  job (:doc:`documentation </core-concepts/item-job>`).
* `JobWithChildJobs <https://github.com/yokai-php/batch/tree/0.x/src/Job/JobWithChildJobs.php>`__: a job that
  trigger other jobs (:doc:`documentation </core-concepts/job-with-children>`).
* `TriggerScheduledJobsJob <https://github.com/yokai-php/batch/tree/0.x/src/Trigger/TriggerScheduledJobsJob.php>`__:
  a job that trigger other jobs when schedule is due (todo documentation).

**Jobs from bridges:**

* From ``league/flysystem`` bridge:

  * `CopyFilesJob <https://github.com/yokai-php/batch-league-flysystem/blob/0.x/src/Job/CopyFilesJob.php>`__:
    copy files from one filesystem to another.
  * `MoveFilesJob <https://github.com/yokai-php/batch-league-flysystem/blob/0.x/src/Job/MoveFilesJob.php>`__:
    move files from one filesystem to another.

.. seealso::
   | :doc:`How do I start a job? </core-concepts/job-launcher>`
   | :doc:`How do I build a batch processing job? </core-concepts/item-job>`
   | :doc:`How do I access parameters of a job? </core-concepts/job-parameter-accessor>`
