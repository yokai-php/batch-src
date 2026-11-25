# UPGRADE FROM 0.7 to 0.8

Version 0.8 adds support for Symfony 8.0 & 8.1.
It does not change the job, reader, processor or writer APIs,
but it drops support for older PHP, Symfony and Doctrine versions,
and it renames the services registered by the Symfony bundle.

## Requirements

All `yokai/batch*` packages must be upgraded together: they now require each other with `^0.8.0`.

### PHP

PHP **8.2** or higher is now required (PHP 8.1 support was dropped).

### Symfony

Symfony **7.4** or **8.x** is now required (`^7.4|^8.0`).
Symfony 6.4 and 7.0 to 7.3 are not supported anymore.

This applies to every Symfony component used by the packages:
`config`, `console`, `dependency-injection`, `filesystem`, `framework-bundle`, `http-kernel`,
`messenger`, `process`, `serializer`, `uid` and `validator`
(and `form`, `security-bundle`, `translation` and `twig-bundle` if you are using the user interface).

> [!NOTE]
> Symfony 8.x requires PHP 8.4 or higher.

### Doctrine

| Package                 | 0.7               | 0.8      | Used by                                                     |
|-------------------------|-------------------|----------|-------------------------------------------------------------|
| `doctrine/dbal`         | `^2.11 \|\| ^3.0` | `^4.0`   | `yokai/batch-doctrine-dbal`                                 |
| `doctrine/orm`          | `^2.8`            | `^3.0`   | `yokai/batch-doctrine-orm`                                  |
| `doctrine/persistence`  | `^2.0 \|\| ^3.0`  | `^4.0`   | `yokai/batch-doctrine-dbal`, `-orm`, `-persistence`         |

If your application is still using Doctrine DBAL 2/3 or Doctrine ORM 2,
upgrade Doctrine first by following the
[DBAL](https://github.com/doctrine/dbal/blob/4.0.x/UPGRADE.md) and
[ORM](https://github.com/doctrine/orm/blob/3.0.x/UPGRADE.md) upgrade guides.

The table used by `DoctrineDBALJobExecutionStorage` is unchanged: no data migration is required.

## Symfony bundle (`yokai/batch-symfony-framework`)

### Routes must be imported from a PHP file

The bundle configuration files were converted from XML to PHP.
The `routing/ui.xml` file does not exist anymore: import `routing/ui.php` instead.

```diff
 # config/routes/yokai_batch.yaml
 _yokai_batch:
-  resource: "@YokaiBatchBundle/Resources/routing/ui.xml"
+  resource: "@YokaiBatchBundle/Resources/routing/ui.php"
```

Route names (`yokai_batch.job_list`, `yokai_batch.job_view`, `yokai_batch.job_view_child`, `yokai_batch.job_logs`)
and paths are unchanged.

### Service identifiers changed

Every service registered by the bundle is now identified by its class name (or interface name)
instead of a `yokai_batch.*` identifier.
The old identifiers do not exist anymore, not even as aliases.

Most applications are not affected: these services are private and were already autowired using class names.
You must only update your code if you were referencing a `yokai_batch.*` service identifier
in your configuration (`@yokai_batch.…` in YAML, `service('yokai_batch.…')` in PHP, a decorator, a compiler pass,
a `storage.service` or `ui.templating.service` bundle option, a route `_controller`, …).

| 0.7 service identifier                                 | 0.8 service identifier                                                                |
|--------------------------------------------------------|---------------------------------------------------------------------------------------|
| `yokai_batch.job_execution_factory`                    | `Yokai\Batch\Factory\JobExecutionFactory`                                             |
| `yokai_batch.job_registry`                             | `Yokai\Batch\Registry\JobRegistry`                                                    |
| `yokai_batch.job_execution_accessor`                   | `Yokai\Batch\Job\JobExecutionAccessor`                                                |
| `yokai_batch.job_executor`                             | `Yokai\Batch\Job\JobExecutor`                                                         |
| `yokai_batch.job_execution_parameters_builder.chain`   | `Yokai\Batch\Factory\JobExecutionParametersBuilderInterface`                          |
| `yokai_batch.job_execution_serializer.json`            | `Yokai\Batch\Serializer\JsonJobExecutionSerializer`                                   |
| `yokai_batch.logger`                                   | `Yokai\Batch\Logger\BatchLogger`                                                      |
| `yokai_batch.storage.dbal`                             | `Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALJobExecutionStorage`                    |
| `yokai_batch.storage.filesystem`                       | `Yokai\Batch\Storage\FilesystemJobExecutionStorage`                                   |
| `yokai_batch.item_writer.doctrine_orm_object_writer`   | `Yokai\Batch\Bridge\Doctrine\Persistence\ObjectWriter`                                |
| `yokai_batch.item_processor.normalize_item_processor`  | `Yokai\Batch\Bridge\Symfony\Serializer\NormalizeItemProcessor`                        |
| `yokai_batch.item_processor.skip_invalid_item_processor` | `Yokai\Batch\Bridge\Symfony\Validator\SkipInvalidItemProcessor`                     |
| `yokai_batch.run_job_command`                          | `Yokai\Batch\Bridge\Symfony\Console\RunJobCommand`                                    |
| `yokai_batch.cli.setup_storage_command`                | `Yokai\Batch\Bridge\Symfony\Console\SetupStorageCommand`                              |
| `yokai_batch.messenger.launch_job_message_handler`     | `Yokai\Batch\Bridge\Symfony\Messenger\LaunchJobMessageHandler`                        |
| `yokai_batch.ui.controller`                            | `Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Controller\JobController`         |
| `yokai_batch.ui.security`                              | `Yokai\Batch\Bridge\Symfony\Framework\UserInterface\JobSecurity`                      |
| `yokai_batch.ui.twig_extension`                        | `Yokai\Batch\Bridge\Symfony\Framework\UserInterface\TwigExtension`                    |
| `yokai_batch.ui.filter_form`                           | `Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Form\JobFilterType`               |
| `yokai_batch.ui.sonata_templating`                     | `Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\SonataAdminTemplating` |
| `yokai_batch.processor.null`                           | *removed*, register `Yokai\Batch\Job\Item\Processor\NullProcessor` yourself if needed |

For instance, if you configured the filesystem storage serializer explicitly:

```diff
 # config/packages/yokai_batch.yaml
 yokai_batch:
   storage:
     filesystem:
-      serializer: yokai_batch.job_execution_serializer.json
+      serializer: Yokai\Batch\Serializer\JsonJobExecutionSerializer
```

The `sonata` shortcut of the `ui.templating` option is still available and does not need any change.

### New service: `ObjectRegistry`

When `yokai/batch-doctrine-orm` is installed, the bundle now also registers
`Yokai\Batch\Bridge\Doctrine\Persistence\ObjectRegistry` as a service.

## Deprecations triggered by Symfony 8.1

The bundle registers named autowiring aliases:
`$yokaiBatchLogger` for the batch logger, and `${name}JobLauncher` for each configured job launcher.

Since Symfony 8.1, relying solely on the argument name to match a named autowiring alias is deprecated.
Add the `#[Target]` attribute (available since Symfony 5.3) to the arguments relying on these aliases:

```diff
+use Symfony\Component\DependencyInjection\Attribute\Target;

 final class YourService
 {
     public function __construct(
+        #[Target('yokaiBatchLogger')]
         private LoggerInterface $yokaiBatchLogger,
+        #[Target('messengerJobLauncher')]
         private JobLauncherInterface $messengerJobLauncher,
     ) {
     }
 }
```

The default job launcher (`JobLauncherInterface $jobLauncher`) is not a named alias and does not need the attribute.
