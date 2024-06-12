# Getting Started

## Project Overview

For a step-by-step presentation of the batch library, we're going to focus on a simple requirement.
Update products and stocks via an Excel import.


### What is a job technically ?
A job is the class that is responsible for **what** your code is doing.

This is the class you will have to create (or reuse),
as it contains the business logic required for what you wish to achieve.

The only requirement is implementing [`JobInterface`](../../src/batch/src/Job/JobInterface.php),
```php
<?php

declare(strict_types=1);

use Yokai\Batch\JobExecution;
use Yokai\Batch\Job\JobInterface;

class DoStuffJob implements JobInterface
{
    public function execute(JobExecution $jobExecution) : void
    {
        // your logic here
    }
}
```

### How to launch a job ?

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

#### More information you need know:
##### JobExecution:

A [JobExecution](../../src/batch/src/JobExecution.php) is the class that holds information about one execution of a job.
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

#### JobExecutionStorage

Whenever a job is launched, whether is starts immediately or not, an execution is stored for it.
The execution are stored to allow you to keep an eye on what is happening.
This persistence is on the responsibility of the job execution storage.
- [Job Execution Storage](../batch/domain/job-execution-storage.md)
