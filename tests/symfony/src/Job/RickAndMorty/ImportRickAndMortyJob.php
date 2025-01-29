<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty;

use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\AbstractDecoratedJob;
use Yokai\Batch\Job\JobExecutor;
use Yokai\Batch\Job\JobWithChildJobs;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * Rick and Morty entities meta-import job.
 *  - {@see ImportRickAndMortyCharacterJob} : import characters
 */
final class ImportRickAndMortyJob extends AbstractDecoratedJob implements JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'rick-and-morty.import';
    }

    public function __construct(JobExecutionStorageInterface $executionStorage, JobExecutor $jobExecutor)
    {
        parent::__construct(
            new JobWithChildJobs($executionStorage, $jobExecutor, [
                // in that case, job order matters
                ImportRickAndMortyEpisodeJob::getJobName(),
                ImportRickAndMortyLocationJob::getJobName(),
                ImportRickAndMortyCharacterJob::getJobName(),
            ]),
        );
    }
}
