<?php

declare(strict_types=1);

use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\PsrLogMessageProcessor;
use Yokai\Batch\Bridge\Monolog\StreamJobExecutionLoggerFactory;

new StreamJobExecutionLoggerFactory(
    directory: '/var/log/batch',
    processors: [new PsrLogMessageProcessor()],
    formatter: new JsonFormatter(),
);
