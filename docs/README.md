## Installation

```bash
composer require yokai/batch
```

## What is Batch ?
The Batch library solves all your massive data processing problems.

Batch can also be used as an ETL.

Batch can also work asynchronously.

Batch help you to make reporting during process.

## How it works ?
Batch is a library that allows you to declare and execute jobs.
And having control over each step of the process to be able to extend the technical logic to meet business needs.

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

#### See More:
For more information about jobs, see [Job](batch/domain/job.md)

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

### JobExecution:

A [JobExecution](../src/batch/src/JobExecution.php) is the class that holds information about one execution of a job.

### JobExecutionStorage

Whenever a job is launched, whether is starts immediately or not, an execution is stored for it.
The execution are stored to allow you to keep an eye on what is happening.
This persistence is on the responsibility of the job execution storage.
- [Job Execution Storage](batch/domain/job-execution-storage.md)
