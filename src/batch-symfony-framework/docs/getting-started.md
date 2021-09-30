# Getting started

## Configuring the bundle

```php
// config/bundles.php
return [
    // ...
    Yokai\Batch\Bridge\Symfony\Framework\YokaiBatchBundle::class => ['all' => true],
];
```

```yaml
# config/packages/yokai_batch.yaml
yokai_batch:
    storage:
        filesystem: ~
        # Or with yokai/batch-doctrine-dbal (& doctrine/dbal)
        # dbal: ~
```

## Job Example

Let say you have a Doctrine ORM entity `App\Entity\User`,
and a [JSON Lines](https://jsonlines.org/) file with information about these entities.

Your goal is to import this file in the database.

### Job in YAML

```yaml
# config/packages/yokai_batch.yaml (or anywhere else)
services:
  job.import_users:
    class: Yokai\Batch\Job\Item\ItemJob
    tags: ['yokai_batch.job']
    arguments:
      $batchSize: 500
      $reader: !service
        class: Yokai\Batch\Job\Item\Reader\Filesystem\JsonLinesReader
        arguments:
          $filePath: !service
            class: Yokai\Batch\Job\Parameters\DefaultParameterAccessor
            arguments:
              $accessor: !service
                class: Yokai\Batch\Job\Parameters\JobExecutionParameterAccessor
                arguments: ['importFile']
              $default: '%kernel.project_dir%/var/import/users.jsonl'
      $processor: !service
        class: Yokai\Batch\Bridge\Symfony\Serializer\DenormalizeItemProcessor
        arguments:
          $denormalizer: '@serializer'
          $type: App\Entity\User
      $writer: '@yokai_batch.item_writer.doctrine_orm_object_writer'
```

Then the job will be triggered with its service id:

```php
/** @var \Yokai\Batch\Launcher\JobLauncherInterface $launcher */
$launcher->launch('job.import_users');
```

### Job in sources

Although it is 100% possible to register jobs via a YAML file it can become very tedious.

As Symfony supports registering all classes in `src/` as a service,
we can leverage this behaviour to register all jobs in `src/`.

```yaml
# config/services.yaml
services:
  _defaults:
    _instanceof:
      Yokai\Batch\Job\JobInterface:
        tags: ['yokai_batch.job']
```

```php
<?php

namespace App\Job;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectWriter;
use Yokai\Batch\Bridge\Symfony\Serializer\DenormalizeItemProcessor;
use Yokai\Batch\Job\Item\ItemJob;
use Yokai\Batch\Job\Item\Reader\Filesystem\JsonLinesReader;
use Yokai\Batch\Job\Parameters\DefaultParameterAccessor;
use Yokai\Batch\Job\Parameters\JobExecutionParameterAccessor;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

final class ImportUsersJob extends ItemJob
{
    public function __construct(
        JobExecutionStorageInterface $executionStorage,
        ManagerRegistry $doctrine,
        DenormalizerInterface $denormalizer,
        KernelInterface $kernel
    ) {
        parent::__construct(
            500,
            new JsonLinesReader(
                new DefaultParameterAccessor(
                    new JobExecutionParameterAccessor('importFile'),
                    $kernel->getProjectDir() . '/var/import/users.jsonl'
                )
            ),
            new DenormalizeItemProcessor($denormalizer, User::class),
            new ObjectWriter($doctrine),
            $executionStorage
        );
    }
}
```

Then the job will be triggered with its service id:

```php
/** @var \Yokai\Batch\Launcher\JobLauncherInterface $launcher */
$launcher->launch(\App\Job\ImportUsersJob::class);
```
