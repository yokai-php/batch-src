<?php

declare(strict_types=1);

use Yokai\Batch\Bridge\Monolog\StreamJobExecutionLoggerFactory;

new StreamJobExecutionLoggerFactory(
    directory: '/var/log/batch',
);
