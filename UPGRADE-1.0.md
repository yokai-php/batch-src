# UPGRADE FROM 0.7 to 1.0

## Requirements

- PHP **8.2** or higher is now required (readonly classes and properties are used throughout the codebase).

## Code changes

### New field: `JobExecution::$launchedAt`

`JobExecution` now tracks when a job was launched (i.e. when `JobLauncherInterface::launch()` was
called), separately from when it started executing.

Two new methods are available:

```php
$execution->getLaunchedAt(): ?DateTimeInterface   // null if not launched yet
$execution->setLaunchedAt(?DateTimeInterface)     // immutable once set, throws ImmutablePropertyException
```

If you create executions through `JobExecutionFactory::create()` (the recommended way), `launchedAt`
is set automatically. No action required.

#### Data migration — `DoctrineDBALJobExecutionStorage`

A new nullable column `launched_at` (datetime) must be added to the job execution table (default:
`yokai_batch_job_execution`).

**Option A — use the built-in `setup()` method**

Call `SetupableJobExecutionStorageInterface::setup()` on the storage service. It is idempotent and
will add the missing column and index without touching existing data.

```php
/** @var \Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALJobExecutionStorage $storage */
$storage->setup();
```

Via the Symfony console command (if `batch-symfony-console` is installed):

```bash
bin/console yokai:batch:setup-storage
```

**Option B — manual migration**

```sql
ALTER TABLE yokai_batch_job_execution
    ADD COLUMN launched_at DATETIME DEFAULT NULL;

CREATE INDEX IDX_yokai_batch_job_execution_launched_at
    ON yokai_batch_job_execution (launched_at);
```

Adapt the column type (`DATETIME`, `TIMESTAMP`, etc.) and the index name to your database platform
and naming conventions.

**Backfill existing rows**

After the column is added, existing rows have `launched_at = NULL`. Backfill them using
`start_time` as a reasonable approximation of when the job was launched:

```sql
UPDATE yokai_batch_job_execution
SET launched_at = start_time
WHERE launched_at IS NULL
  AND start_time IS NOT NULL;
```

#### Data migration — `FilesystemJobExecutionStorage`

Existing JSON files on disk load without error (the deserializer treats a missing `launchedAt` key
as `null`), but the field will remain `null` for all historical executions unless you backfill it.

Use `jq` to add `launchedAt` from `startTime` to every existing file in your batch directory
(default: `var/batch`):

```bash
find var/batch -name '*.json' | while read -r file; do
    jq 'if .launchedAt == null then .launchedAt = .startTime else . end' "$file" > "$file.tmp" \
        && mv "$file.tmp" "$file"
done
```

Adjust the path (`var/batch`) to match your configured storage directory.

---

### Logger architecture refactoring (BREAKING)

The monolithic `JobExecutionLogger` class and `BatchLogger` class have been removed and replaced by
a small, pluggable interface hierarchy.

#### Removed classes

| 0.x class | Replacement |
|-----------|-------------|
| `Yokai\Batch\JobExecutionLogger` | `Yokai\Batch\Logger\InMemoryJobExecutionLogger` |
| `Yokai\Batch\Logger\BatchLogger` | `Yokai\Batch\Logger\YokaiBatchLogger` |
| `Yokai\Batch\JobExecutionLogs` | No direct equivalent — logs are now managed by `JobExecutionLoggerInterface` |

#### New interfaces and classes

| Class / Interface | Description |
|-------------------|-------------|
| `Yokai\Batch\Logger\JobExecutionLoggerInterface` | PSR-3 logger + `getReference()`, `getLogs()`, `getLogsContent()` |
| `Yokai\Batch\Factory\JobExecutionLoggerFactoryInterface` | Creates and restores loggers (`create()` / `restore(string $ref)`) |
| `Yokai\Batch\Logger\InMemoryJobExecutionLogger` | Default implementation — accumulates logs in-memory |
| `Yokai\Batch\Logger\NullJobExecutionLogger` | Discards all logs — useful in tests |
| `Yokai\Batch\Factory\JobExecutionLoggerFactory\InMemoryJobExecutionLoggerFactory` | Factory for the in-memory logger (registered by default by the Symfony bundle) |
| `Yokai\Batch\Factory\JobExecutionLoggerFactory\NullJobExecutionLoggerFactory` | Factory for the null logger |
| `Yokai\Batch\Logger\YokaiBatchLogger` | Replaces `BatchLogger` — PSR-3 event listener that forwards logs to the active execution logger |

#### `JobExecution::getLogger()` return type changed

```diff
-public function getLogger(): LoggerInterface
+public function getLogger(): JobExecutionLoggerInterface  // extends LoggerInterface
```

This is backwards-compatible for code that only calls PSR-3 methods on the returned logger.

#### Constructor changes — `JobExecutionFactory`

`JobExecutionFactory` now requires a third argument:

```diff
new JobExecutionFactory(
    $idGenerator,
    $parametersBuilder,
+    new InMemoryJobExecutionLoggerFactory(), // any implementation of JobExecutionLoggerFactoryInterface
);
```

In a Symfony application the bundle wires this automatically.

#### Constructor changes — `DoctrineDBALJobExecutionStorage`

`DoctrineDBALJobExecutionStorage` now requires a `JobExecutionLoggerFactoryInterface` as its
second argument (before the options array):

```diff
new DoctrineDBALJobExecutionStorage(
    $doctrine,
+    new InMemoryJobExecutionLoggerFactory(), // any implementation of JobExecutionLoggerFactoryInterface
    $options,
);
```

In a Symfony application the bundle wires this automatically.

#### Constructor changes — `JsonJobExecutionSerializer`

`JsonJobExecutionSerializer` now requires a `JobExecutionLoggerFactoryInterface`:

```diff
new JsonJobExecutionSerializer(
+    new InMemoryJobExecutionLoggerFactory(), // any implementation of JobExecutionLoggerFactoryInterface
);
```

In a Symfony application the bundle wires this automatically.

#### Replacing `BatchLogger` with `YokaiBatchLogger`

`BatchLogger` and `YokaiBatchLogger` are functionally identical — both are PSR-3 loggers that
forward to the current job execution logger during job execution. Replace any references to
`BatchLogger` with `YokaiBatchLogger`.

---

### Symfony bundle configuration — storage DSN

The `storage` key of the `yokai_batch` bundle now accepts a **DSN string** as a shorthand:

```yaml
# 1.x — new shorthand DSN form
yokai_batch:
    storage: 'filesystem://%kernel.project_dir%/var/batch'
    # or
    storage: 'dbal://default?table=yokai_batch_job_execution'
    # or
    storage: 'service://?id=App\BatchStorage\MyCustomStorage'
```

The existing verbose forms (`storage.filesystem`, `storage.dbal`, `storage.service`) continue to
work and require no change.

---

### Symfony bundle configuration — UI pagination

New options let you tune the job list UI pagination:

```yaml
# 1.x — default values shown
yokai_batch:
    ui:
        pagination:
            page_size: 20   # number of executions per page
            page_range: 2   # number of page links shown on each side of the current page
```
