# Getting started

## Configuring the bundle

```php
// config/bundles.php
return [
    // ...
    Yokai\Batch\Bridge\Symfony\Framework\YokaiBatchBundle::class => ['all' => true],
];
```

### Job launcher

You can use many different job launcher in your application, you will be able to register these using configuration:

```yaml
# config/packages/yokai_batch.yaml
yokai_batch:
    launcher:
        default: simple
        launchers:
          simple: ...
          async: ...
```

> **note**: if you do not configure anything here, you will be using the [`SimpleJobLauncher`](../../src/batch/src/Launcher/SimpleJobLauncher.php).

The `default` job launcher, must reference a launcher name, defined in the `launchers` list.
The `default` job launcher will be the autowired instance of job launcher when you ask for one.
All `launchers` will be registered as a service, and an autowire named alias will be configured for it.
For instance, in the example below, you will be able to register all these launchers like this:

```php
<?php

use Yokai\Batch\Launcher\JobLauncherInterface;

final class YourAppCode
{
    public function __construct(
        private JobLauncherInterface $jobLauncher, // will inject the default job launcher
        private JobLauncherInterface $simpleJobLauncher, // will inject the "simple" job launcher
        private JobLauncherInterface $messengerJobLauncher, // will inject the "messenger" job launcher
    ) {
    }
}
```

All `launchers` are configured using a DSN, every scheme has it's own associated factory.
- `simple://simple`: a [`SimpleJobLauncher`](../../src/batch/src/Launcher/SimpleJobLauncher.php), no configuration allowed
- `messenger://messenger`: a [`DispatchMessageJobLauncher`](../../src/batch-symfony-messenger/src/DispatchMessageJobLauncher.php), no configuration allowed
- `console://console`: a [`RunCommandJobLauncher`](../../src/batch-symfony-console/src/RunCommandJobLauncher.php), configurable options:
  - `log`: the filename where command output will be redirected (defaults to `batch_execute.log`)
- `service://service`: pointing to a service of your choice, configurable options:
  - `service`: the id of the service to use (required, an exception will be thrown otherwise)

### JobExecution storage

You can have only one storage for your `JobExecution`, and you have several options:
- `filesystem` will create a file for each `JobExecution` in `%kernel.project_dir%/var/batch/{execution.jobName}/{execution.id}.json`
- `dbal` will create a row in a table for each `JobExecution`
- `service` will use a service you have defined in your application

```yaml
# config/packages/yokai_batch.yaml
yokai_batch:
    storage:
        filesystem: ~
        # Or with yokai/batch-doctrine-dbal (& doctrine/dbal)
        # dbal: ~
        # Or with a service of yours
        # service: ~
```

> **note**: the default storage is `filesystem`, because it only requires a writeable filesystem.
> But if you already have `doctrine/dbal` in your project, it is highly recommended to use it instead.
> Because querying `JobExecution` in a filesystem might be slow, specially if you are planing to add UIs on top.

### Job as a service

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
> [JobWithStaticNameInterface](../../src/batch-symfony-framework/src/JobWithStaticNameInterface.php) interface
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
        private JobLauncherInterface $jobLauncher,
    ) {
    }

    public function method(): void
    {
        $this->jobLauncher->launch('job.name');
    }
}
```

The job launcher that will be injected depends on the packages you have installed, order matter:
- if `yokai/batch-symfony-messenger` is installed, you will receive a `Yokai\Batch\Bridge\Symfony\Messenger\DispatchMessageJobLauncher`
- if `yokai/batch-symfony-console` is installed, you will receive a `Yokai\Batch\Bridge\Symfony\Console\RunCommandJobLauncher`
- otherwise you will receive a `Yokai\Batch\Launcher\SimpleJobLauncher`


## Use the batchLogger
The aim of the batchLogger is to store log inside the jobExecution. 
In a symfony project, you can use the batchLogger with the symfony autowiring by naming your variable as `$yokaiBatchLogger` 

```php
<?php

namespace App;

use Psr\Log\LoggerInterface;

final readonly class YourService
{
    public function __construct(
        private LoggerInterface $yokaiBatchLogger,
    ) {
    }

    public function method()
    {
        $this->yokaiBatchLogger->error(...);
    }
}
```

## On the same subject

- [What is a job execution storage ?](../batch/domain/job-execution-storage.md)
- [What is a job ?](../batch/domain/job.md)
- [What is a job launcher ?](../batch/domain/job-launcher.md)
