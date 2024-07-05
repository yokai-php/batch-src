Getting started as a standalone library
============================================================

.. note::

    | If you already have a running app, you might consider a different *getting started* guide,
      based on the framework your application is working on:

    * :doc:`/getting-started/with-symfony`

Installation
------------------------------------------------------------

.. code-block:: console

    composer require yokai/batch

.. note::

    | ``yokai/batch`` is the only package required.
    | But there are many bridge packages you can install if you want to unlock more components.
    | Have a look to dedicated documentation: :doc:`</bridges>`.

Step by step example
------------------------------------------------------------

As a developer, from your application, you want to launch a job.

.. code-block:: php

    <?php

    use Yokai\Batch\Launcher\SimpleJobLauncher;

    $launcher = new SimpleJobLauncher(...);

    $launcher->launch('import', ['path' => '/path/to/file/to/import']);

| This job will be executed by the ``JobLauncher``, and at some point in the call graph, your code will run.
| This logic have to be implemented in a ``Job``.

.. code-block:: php

    <?php

    use Yokai\Batch\Job\JobInterface;
    use Yokai\Batch\JobExecution;
    use Yokai\Batch\Launcher\SimpleJobLauncher;

    new class implements JobInterface {
        public function execute(JobExecution $jobExecution): void
        {
            $fileToImport = $jobExecution->getParameter('path');
            // your import logic here
        }
    };

    $launcher = new SimpleJobLauncher(...);

    $launcher->launch('import', ['path' => '/path/to/file/to/import']);

The JobLauncher will have to be provided with all the jobs you create in your application, so it can launch any of it.

.. code-block:: php

    <?php

    use Yokai\Batch\Job\JobInterface;
    use Yokai\Batch\JobExecution;
    use Yokai\Batch\Launcher\SimpleJobLauncher;
    use Yokai\Batch\Registry\JobContainer;
    use Yokai\Batch\Registry\JobRegistry;

    $container = new JobContainer([
        'import' => new class implements JobInterface {
            public function execute(JobExecution $jobExecution): void
            {
                $fileToImport = $jobExecution->getParameter('path');
                // your import logic here
            }
        },
    ]);

    $launcher = new SimpleJobLauncher(
        ...,
        new JobExecutor(
            new JobRegistry($container),
            ...
        )
    );

    $launcher->launch('import', ['path' => '/path/to/file/to/import']);

.. note::

    | ``JobContainer`` is an implementation of a `PSR11 container <https://www.php-fig.org/psr/psr-11/>`__.
    | You can use it if you want, but you can replace it with any implementation from your application.

| But now, what if the job fails, or what if you wish to analyse what the job produced.
| You need to a able to store JobExecution, so you can fetch it afterwards.

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
    use Yokai\Batch\Serializer\JsonJobExecutionSerializer;
    use Yokai\Batch\Storage\FilesystemJobExecutionStorage;

    $container = new JobContainer([
        'import' => new class implements JobInterface {
            public function execute(JobExecution $jobExecution): void
            {
                $fileToImport = $jobExecution->getParameter('path');
                // your import logic here
            }
        },
    ]);

    $jobExecutionStorage = new FilesystemJobExecutionStorage(new JsonJobExecutionSerializer(), '/dir/where/jobs/are/stored');
    $launcher = new SimpleJobLauncher(
        new JobExecutionAccessor(
            new JobExecutionFactory(new UniqidJobExecutionIdGenerator(), new NullJobExecutionParametersBuilder()),
            $jobExecutionStorage
        ),
        new JobExecutor(
            new JobRegistry($container),
            $jobExecutionStorage,
            null // or an instance of \Psr\EventDispatcher\EventDispatcherInterface
        )
    );

    $importExecution = $launcher->launch('import', ['path' => '/path/to/file/to/import']);

There you go, you have a fully functional stack to start working with the library.
