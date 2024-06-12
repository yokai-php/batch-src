# Getting started
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
- **A Storage for Job Executions:** A storage is a way to store the execution of a job, it can be a database, a file, a cache, etc.
- **A JobExecutor:** The executor is the class that will execute the job.
- **A JobExecutionAccessor:** The accessor is the class that will access the job execution.

But don't worry, `Yokai\batch` give you all the tools you need to start.

You can start by build a `JobContainer` for store your jobs.
```php
$jobs = new JobContainer([
    'your.job.name' => new class implements JobInterface {
        public function execute(JobExecution $jobExecution): void
        {
            // your business logic
        }
    },
]);
```

For the storage you can use the `NullJobExecutionStorage`.
```php
$jobExecutionStorage = new NullJobExecutionStorage();
```
If you want see more Storage options, see [Job Execution Storage](batch/domain/job-execution-storage.md)

Next, we can build the `JobLauncher` with the `SimpleJobLauncher` implementation and everything else needed to build it.
```php
$launcher = new SimpleJobLauncher(
    new JobExecutionAccessor(new JobExecutionFactory(new UniqidJobExecutionIdGenerator()), $jobExecutionStorage),
    new JobExecutor(new JobRegistry($jobs), $jobExecutionStorage, null),
);
```

Finally, you will get a script who seems like this:

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
If you want know more you can look one of this:
- [Job Launcher](batch/domain/job-launcher.md)
- [Job Execution](batch/domain/job-execution.md)
- [Job Execution Storage](batch/domain/job-execution-storage.md)

## Step-by-step example

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
