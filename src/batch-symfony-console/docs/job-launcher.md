# Job launcher

The [RunCommandJobLauncher](../src/RunCommandJobLauncher.php) execute jobs via an asynchronous symfony command.

The command called is [`yokai:batch:run`](command.md), and the command will actually execute the job.

Additionally, the command will run with an output redirect (`>>`) to `var/log/batch_execute.log`.


## On the same subject

- [What is a job launcher ?](https://github.com/yokai-php/batch/blob/0.x/docs/domain/job-launcher.md)
