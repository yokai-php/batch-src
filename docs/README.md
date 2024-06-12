## Installation

```bash
composer require yokai/batch
```

## Vocabulary

Because when you start with any library
it is important to understand what are the concepts introduced in it.

This is highly recommended that you read this entire page
before starting to work with this library.

### Job
A job is the class that is responsible for **what** your code is doing.

This is the class you will have to create (or reuse),
as it contains the business logic required for what you wish to achieve.

The only requirement is implementing [`JobInterface`](../src/batch/src/Job/JobInterface.php),

For exemple you can have a job for generate a csv:
```php
<?php

declare(strict_types=1);

use Yokai\Batch\JobExecution;
use Yokai\Batch\Job\JobInterface;

class CsvExportJob implements JobInterface
{
    public function execute(JobExecution $jobExecution) : void
    {
        $file = fopen('path/to/file.csv', 'w');
            
        // your export logic here
        fputcsv($file, ['column1', 'column2']);
        
        fclose($file);
    }
}
```
#### See More:
For more information about jobs, see [Job](batch/domain/job.md)

[//]: # (Todo: Maybe we can remove Job.md and put the content here)
### Job Launcher

The job launcher is responsible for executing/scheduling every jobs.

Yeah, executing OR scheduling. There is multiple implementation of a job launcher across bridges.
Job's execution might be asynchronous, and thus, when you ask the job launcher to "launch" a job,
you have to check the `JobExecution` status that it had returned to know if the job is already executed.

#### What is needed to use a Job Launcher ?

For use a Job Launcher you need to have:
- **A Container of Jobs:** A container use any PSR-11 [container implementation](https://packagist.org/providers/psr/container-implementation)
- **A Storage for Job Executions:** A storage to store the execution of jobs, it can be a database, a file, a cache, etc.
- **A JobExecutor:** The executor will execute the job.
- **A JobExecutionAccessor:** The accessor will access the job execution.

#### How to use a Job Launcher ?
Look here a simple example to use a Job Launcher with the `SimpleJobLauncher`:

```php
<?php

declare(strict_types=1);

use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\Factory\UniqidJobExecutionIdGenerator;
use Yokai\Batch\Job\JobExecutionAccessor;
use Yokai\Batch\Job\JobExecutor;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Launcher\SimpleJobLauncher;
use Yokai\Batch\Registry\JobContainer;
use Yokai\Batch\Registry\JobRegistry;
use Yokai\Batch\Storage\NullJobExecutionStorage;

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
    new JobExecutionAccessor(new JobExecutionFactory(new UniqidJobExecutionIdGenerator()), $jobExecutionStorage),
    new JobExecutor(new JobRegistry($jobs), $jobExecutionStorage, null),
);

$execution = $launcher->launch('your.job.name', ['job' => ['configuration']]);
```

#### See More:
- [Job Launcher](batch/domain/job-launcher.md)

### JobExecution:

A [JobExecution](../src/batch/src/JobExecution.php) is the class that holds information about one execution of a job.
#### What kind of information does it hold ?

- `JobExecution::$jobName` : The Job name (job id)
- `JobExecution::$id` : The execution id
- `JobExecution::$parameters` : Some parameters with which job was executed
- `JobExecution::$status` : A status (pending, running, stopped, completed, abandoned, failed)
- `JobExecution::$startTime` : Start time
- `JobExecution::$endTime` : End time
- `JobExecution::$failures` : A list of failures (usually exceptions)
- `JobExecution::$warnings` : A list of warnings (usually skipped items)
- `JobExecution::$summary` : A summary (can contain any data you wish to store)
- `JobExecution::$logs` : Some logs
- `JobExecution::$childExecutions` : Some child execution

### JobExecutionStorage

Whenever a job is launched, whether is starts immediately or not, an execution is stored for it.
The execution are stored to allow you to keep an eye on what is happening.
This persistence is on the responsibility of the job execution storage.
- [Job Execution Storage](batch/domain/job-execution-storage.md)


## Next steps
- [Getting Started (Nom de l'exemple)](getting-started.md)
- Framework:
  - [Symfony](symfony.md)
- Bridge:
  - Doctrine:
    - [Dbal](batch-doctrine-dbal/job-execution-storage.md)
    - [ORM](batch-doctrine-orm/entity-item-reader.md)
    - [Persistence](batch-doctrine-persistence/object-registry.md)

[//]: # (    Todo: link object-item-writer.md ?)
- [Flysystem bridge](flysystem.md)
- [Box OpenSpout bridge](openspout.md)

[//]: # (Todo: La partie symfony est-elle vraiment utile ? Ou peut être que l'on peut la mettre dans une catégorie a pat entiére ?)
- Symfony:
  - [Console](batch-symfony-console/command.md)
  - [Messenger](batch-symfony-messenger/job-launcher.md)
  - [Serializer](batch-symfony-serializer/job-execution-serializer.md)
  - [Validator](batch-symfony-validator/skip-invalid-item-processor.md)

[//]: # (Idée d'exemple pour le getting started:)
[//]: # (- Un import like Cizeta sans symfony)
