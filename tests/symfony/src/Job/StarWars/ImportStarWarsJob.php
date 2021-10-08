<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars;

use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\JobWithChildJobs;
use Yokai\Batch\Registry\JobRegistry;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * Star Wars entities meta-import job.
 *  - {@see ImportStarWarsPlanetJob} : import planets
 *  - {@see ImportStarWarsSpecieJob} : import species
 *  - {@see ImportStarWarsCharacterJob} : import characters
 */
final class ImportStarWarsJob extends JobWithChildJobs implements JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'star-wars.import';
    }

    public function __construct(JobExecutionStorageInterface $executionStorage, JobRegistry $jobRegistry)
    {
        parent::__construct($executionStorage, $jobRegistry, [
            ImportStarWarsPlanetJob::getJobName(),
            ImportStarWarsSpecieJob::getJobName(),
            // this job should always be last
            // because it expects other entities to have been imported
            ImportStarWarsCharacterJob::getJobName(),
        ]);
    }
}
