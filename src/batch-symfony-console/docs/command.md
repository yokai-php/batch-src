# Command

The [RunJobCommand](../src/RunJobCommand.php) can execute any job.

The command accepts 2 arguments :
- the job name to execute
- the job parameters for the `JobExecution` (optional)

Examples :
```
bin/console import
bin/console export '{"toFile":"/path/to/file.xml"}'
```
