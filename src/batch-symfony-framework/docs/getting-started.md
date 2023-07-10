# Getting started

## Configuring the bundle

```php
// config/bundles.php
return [
    // ...
    Yokai\Batch\Bridge\Symfony\Framework\YokaiBatchBundle::class => ['all' => true],
];
```

There is few things that can be configured in the bundle at the moment.
But the most important one will be the `JobExecution` storage:
- `filesystem` will create a file for each `JobExecution` in `%kernel.project_dir%/var/batch/{execution.jobName}/{execution.id}.json`
- `dbal` will create a row in a table for each `JobExecution`

```yaml
# config/packages/yokai_batch.yaml
yokai_batch:
    storage:
        filesystem: ~
        # Or with yokai/batch-doctrine-dbal (& doctrine/dbal)
        # dbal: ~
```

> **note**: the default storage is `filesystem`, because it only requires a writeable filesystem.
> But if you already have `doctrine/dbal` in your project, it is highly recommended to use it instead.
> Because querying `JobExecution` in a filesystem might be slow, specially if you are planing to add UIs on top.

As Symfony supports registering all classes in `src/` as a service,
we can leverage this behaviour to register all jobs in `src/`.
We will add a tag to every found class in `src/` that implements `Yokai\Batch\Job\JobInterface`:

```yaml
# config/services.yaml
services:
  _defaults:
    _instanceof:
      Yokai\Batch\Job\JobInterface:
        tags: ['yokai_batch.job']
```

## Your first job

In a Symfony project, we will prefer using one class per job, because service discovery is so easy to use.
But also because it will be very far easier to configure your job using PHP than any other format.
For instance, there is components that uses `Closure`, has static constructors, ...
But keep in mind you can register your jobs with any other format of your choice.

```php
<?php

namespace App\NamespaceOfYourChoice;

use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\JobInterface;

final class NameOfYourJob implements JobInterface, JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'job.name';
    }

    public function execute(JobExecution $jobExecution): void
    {
        // your logic here
    }
}
```

> **note**: when registering jobs with dedicated class, you can use the
> [JobWithStaticNameInterface](../src/JobWithStaticNameInterface.php) interface
> to be able to specify the job name of your service.
> Otherwise, the service id will be used, and in that case, the service id is the FQCN.

### Triggering the job
Then the job will be triggered with its name (or service id when not specified):

```php
<?php

namespace App\MyNamespace;

use Yokai\Batch\Storage\JobExecutionStorageInterface;

final class MyClass
{
    public function __construct(
        private JobLauncherInterface $executionStorage,
    ) {
    }

    public function method(): void
    {
        $this->launcher->launch('job.import_users');
    }
}
```

The job launcher that will be injected depends on the packages you have installed, order matter:
- if `yokai/batch-symfony-messenger` is installed, you will receive a `Yokai\Batch\Bridge\Symfony\Messenger\DispatchMessageJobLauncher`
- if `yokai/batch-symfony-console` is installed, you will receive a `Yokai\Batch\Bridge\Symfony\Console\RunCommandJobLauncher`
- otherwise you will receive a `Yokai\Batch\Launcher\SimpleJobLauncher`

## On the same subject

- [What is a job execution storage ?](https://github.com/yokai-php/batch/blob/0.x/docs/domain/job-execution-storage.md)
- [What is a job ?](https://github.com/yokai-php/batch/blob/0.x/docs/domain/job.md)
- [What is a job launcher ?](https://github.com/yokai-php/batch/blob/0.x/docs/domain/job-launcher.md)
