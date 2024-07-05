<?php

namespace App\Job\Import;

use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\AbstractDecoratedJob;
use Yokai\Batch\Job\JobExecutor;
use Yokai\Batch\Job\JobWithChildJobs;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

final class ImportStarWarsJob extends AbstractDecoratedJob implements JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'starwars_import';
    }

    public function __construct(JobExecutionStorageInterface $executionStorage, JobExecutor $jobExecutor)
    {
        parent::__construct(
            new JobWithChildJobs($executionStorage, $jobExecutor, [
                ImportStarWarsPlanetJob::getJobName(),
                ImportStarWarsSpecieJob::getJobName(),
                ImportStarWarsCharacterJob::getJobName(),
            ]),
        );
    }
}
