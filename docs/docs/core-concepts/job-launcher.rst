Job Launcher
============================================================

What is a job launcher?
------------------------------------------------------------

The job launcher is responsible for executing/scheduling every jobs.

| Yeah, executing OR scheduling. There is multiple implementation of a job launcher across bridges.
| Job’s execution might be asynchronous, and thus, when you ask the job launcher to “launch” a job,
  you have to check the ``JobExecution`` status that it had returned to know if the job is already executed.

What is the simplest way to launch a job?
------------------------------------------------------------

.. code-block:: php

    <?php

    use Yokai\Batch\Factory\JobExecutionFactory;
    use Yokai\Batch\Factory\JobExecutionParametersBuilder\NullJobExecutionParametersBuilder;
    use Yokai\Batch\Factory\UniqidJobExecutionIdGenerator;
    use Yokai\Batch\Job\JobExecutionAccessor;
    use Yokai\Batch\Job\JobExecutor;
    use Yokai\Batch\Job\JobInterface;
    use Yokai\Batch\JobExecution;
    use Yokai\Batch\Launcher\SimpleJobLauncher;
    use Yokai\Batch\Registry\JobContainer;
    use Yokai\Batch\Registry\JobRegistry;
    use Yokai\Batch\Storage\NullJobExecutionStorage;

    // you can instead use any psr/container implementation
    // @see https://packagist.org/providers/psr/container-implementation
    $jobs = new JobContainer([
        'your.job.name' => new class implements JobInterface {
            public function execute(JobExecution $jobExecution): void
            {
                // your business logic
            }
        },
    ]);
    $jobExecutionStorage = new NullJobExecutionStorage();

    $launcher = new SimpleJobLauncher(
        new JobExecutionAccessor(
            new JobExecutionFactory(new UniqidJobExecutionIdGenerator(), new NullJobExecutionParametersBuilder()),
            $jobExecutionStorage,
        ),
        new JobExecutor(new JobRegistry($jobs), $jobExecutionStorage, null),
    );

    $execution = $launcher->launch('your.job.name', ['job' => ['configuration']]);

What types of launcher exists?
------------------------------------------------------------

**Built-in launchers:**

* `SimpleJobLauncher <https://github.com/yokai-php/batch/tree/0.x/src/Launcher/SimpleJobLauncher.php>`__:
  execute the job directly in the same PHP process.

**Launchers from bridges:**

* From ``symfony/console`` bridge:

  * `RunCommandJobLauncher <https://github.com/yokai-php/batch-symfony-console/blob/0.x/src/RunCommandJobLauncher.php>`__:
    execute the job via an asynchronous symfony command.

* From ``symfony/messenger`` bridge:

  * `DispatchMessageJobLauncher <https://github.com/yokai-php/batch-symfony-messenger/blob/0.x/src/DispatchMessageJobLauncher.php>`__:
    execute the job via a symfony message dispatch.

**Launchers for testing purpose:**

* `BufferingJobLauncher <https://github.com/yokai-php/batch/tree/0.x/src/Test/Launcher/BufferingJobLauncher.php>`__:
  do not execute job, but store execution in a private var that can be accessed afterwards in your tests.

.. seealso::
   | :doc:`What is a job? </core-concepts/job>`
   | :doc:`What is a job execution? </core-concepts/job-execution>`
