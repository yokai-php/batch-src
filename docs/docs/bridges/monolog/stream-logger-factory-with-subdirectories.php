<?php

declare(strict_types=1);

use Yokai\Batch\Bridge\Monolog\StreamJobExecutionLoggerFactory;

// With subDirectories: 2, charsPerDirectory: 2, a job execution id
// "60996f72-4f54-4184-9268-35ffdecf0de6" would be stored at:
// /var/log/batch/60/99/60996f72-4f54-4184-9268-35ffdecf0de6.log

new StreamJobExecutionLoggerFactory(
    directory: '/var/log/batch',
    subDirectories: 2,
    charsPerDirectory: 2,
);
