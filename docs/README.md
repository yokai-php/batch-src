# Getting started
## Instalation

```bash
composer require yokai/batch
```

## Vocabulary

Because when you start with any library
it is important to understand what are the concepts introduced in it.

This is highly recommended that you read this entire page
before starting to work with this library.

- [Job](batch/domain/job.md): where you are going to work as a developer.
- [Job Launcher](batch/domain/job-launcher.md): The entry point when you need to execute any job.
- [Job Execution](batch/domain/job-execution.md): The representation of a certain execution of certain job.
- [Job Execution Storage](batch/domain/job-execution-storage.md): The persistence layer of jobs executions.

## Step by step example
As a developer, from your application, you want to launch a job
```php
<?php

declare(strict_types=1);

use Yokai\Batch\Launcher\SimpleJobLauncher;

$launcher = new SimpleJobLauncher(...);

$launcher->launch('import', ['path' => '/path/to/file/to/import']);
```

This job will be executed by the `JobLauncher`, and at some point in the call graph, your code will run. This logic have to be implemented in a Job.
```php
<?php

declare(strict_types=1);

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
```

The JobLauncher will have to be provided with all the jobs you create in your application, so it can launch any of it

:notebook: :notebook: :notebook: 

Note here a container is used to store the jobs, but you can use any PSR-11 container implementation.
For reminder the role of the `JobRegistry` is to register all your job declarations in your container.
For see more refer to [Job Launcher](batch/domain/job-launcher.md)

:notebook: :notebook: :notebook: 

```php
<?php

declare(strict_types=1);

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
```
But now, what if the job fails, or what if you wish to analyse what the job produced. You need to a able to store JobExecution, so you can fetch it afterwards

```php
<?php

declare(strict_types=1);

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
```

There you go, you have a fully functional stack to start working with the library.

[//]: # (Todo::)
[//]: # (## Next steps)

[//]: # (- [Symfony bridge]&#40;symfony.md&#41;)

[//]: # (- [Doctrine bridge]&#40;doctrine.md&#41;)

[//]: # (- [Flysystem bridge]&#40;flysystem.md&#41;)

[//]: # (- [Box OpenSpout bridge]&#40;openspout.md&#41;)
