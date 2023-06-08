<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars;

use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\AbstractDecoratedJob;
use Yokai\Batch\Job\JobExecutor;
use Yokai\Batch\Job\JobWithChildJobs;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * Star Wars entities meta-import job.
 *  - {@see ImportStarWarsPlanetJob} : import planets
 *  - {@see ImportStarWarsSpecieJob} : import species
 *  - {@see ImportStarWarsCharacterJob} : import characters
 */
final class ImportStarWarsJob extends AbstractDecoratedJob implements JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'star-wars.import';
    }

    public function __construct(JobExecutionStorageInterface $executionStorage, JobExecutor $jobExecutor)
    {
        parent::__construct(
            new JobWithChildJobs($executionStorage, $jobExecutor, [
                // in that case, job order matters
                ImportStarWarsPlanetJob::getJobName(),
                ImportStarWarsSpecieJob::getJobName(),
                ImportStarWarsCharacterJob::getJobName(),
            ])
        );
    }
}
