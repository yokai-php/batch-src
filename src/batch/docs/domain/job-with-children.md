# Job with children

todo

```php
<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Job\JobWithChildJobs;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Registry\JobRegistry;
use Yokai\Batch\Registry\JobContainer;
use Yokai\Batch\Serializer\JsonJobExecutionSerializer;
use Yokai\Batch\Storage\FilesystemJobExecutionStorage;

// you can instead use any psr/container implementation
// @see https://packagist.org/providers/psr/container-implementation
$container = new JobContainer([
    // here are the jobs you will want to write
    // each job has an identifier so it can be launched later
    'export' => new class implements JobInterface {
        public function execute(JobExecution $jobExecution): void
        {
            $exportSince = new DateTimeImmutable($jobExecution->getParameter('since'));
            // your export logic here
        }
    },
    'upload' => new class implements JobInterface {
        public function execute(JobExecution $jobExecution): void
        {
            $pathToUploadExportedFile = $jobExecution->getParameter('path');
            // your upload logic here
        }
    },
]);

$job = new JobWithChildJobs(
    new FilesystemJobExecutionStorage(new JsonJobExecutionSerializer(), '/dir/where/jobs/are/stored'),
    new JobRegistry($container),
    ['export', 'upload']
);
```
