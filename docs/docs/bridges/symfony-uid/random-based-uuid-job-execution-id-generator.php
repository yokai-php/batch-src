<?php

declare(strict_types=1);

use Symfony\Component\Uid\Factory\UuidFactory;
use Yokai\Batch\Bridge\Symfony\Uid\Factory\RandomBasedUuidJobExecutionIdGenerator;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\NullJobExecutionParametersBuilder;

(new JobExecutionFactory(
    new RandomBasedUuidJobExecutionIdGenerator(new UuidFactory()),
    new NullJobExecutionParametersBuilder(),
))->create('job.foo');
