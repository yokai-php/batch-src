<?php

declare(strict_types=1);

use Symfony\Component\Uid\Factory\UlidFactory;
use Yokai\Batch\Bridge\Symfony\Uid\Factory\UlidJobExecutionIdGenerator;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\Factory\JobExecutionLoggerFactory\InMemoryJobExecutionLoggerFactory;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\NullJobExecutionParametersBuilder;

(new JobExecutionFactory(
    new UlidJobExecutionIdGenerator(new UlidFactory()),
    new NullJobExecutionParametersBuilder(),
    new InMemoryJobExecutionLoggerFactory(),
))->create('job.foo');
