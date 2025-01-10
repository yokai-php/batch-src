<?php

declare(strict_types=1);

use Yokai\Batch\Job\JobExecutor;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Job\JobWithChildJobs;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Registry\JobContainer;
use Yokai\Batch\Registry\JobRegistry;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/** @var JobExecutionStorageInterface $jobExecutionStorage */

// you can instead use any psr-11 container implementation
// @see https://packagist.org/providers/psr/container-implementation
$container = new JobContainer([
    // here are the jobs you will want to write
    // each job has an identifier so it can be launched later
    'export' => new class implements JobInterface {
        public function execute(JobExecution $jobExecution): void
        {
            $exportSince = new DateTimeImmutable($jobExecution->getParameter('since'));
            // your export logic here
        }
    },
    'upload' => new class implements JobInterface {
        public function execute(JobExecution $jobExecution): void
        {
            $pathToUploadExportedFile = $jobExecution->getParameter('path');
            // your upload logic here
        }
    },
]);

$job = new JobWithChildJobs(
    executionStorage: $jobExecutionStorage,
    jobExecutor: new JobExecutor(
        jobRegistry: new JobRegistry($container),
        jobExecutionStorage: $jobExecutionStorage,
        eventDispatcher: null, // or any implementation of psr-14 event dispatcher
    ),
    childJobs: ['export', 'upload'], // the jobs to execute
);
