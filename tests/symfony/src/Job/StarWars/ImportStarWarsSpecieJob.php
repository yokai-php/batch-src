<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars;

use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectRegistry;
use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars\Planet;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars\Specie;

/**
 * Star Wars {@see Specie} entity import.
 */
final class ImportStarWarsSpecieJob implements JobInterface, JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'star-wars.import:specie';
    }

    public function __construct(
        private readonly ImportStarWarsJobFactory $factory,
    ) {
    }

    public function execute(JobExecution $jobExecution): void
    {
        $this->factory->create(
            __DIR__ . '/../../../data/star-wars/species.csv',
            function (array $item, ObjectRegistry $objectRegistry) {
                $entity = new Specie();
                $entity->name = $item['name'];
                $entity->classification = $item['classification'];
                $entity->language = $item['language'];
                if ($item['homeworld']) {
                    $entity->homeWorld = $objectRegistry->findOneBy(Planet::class, ['name' => $item['homeworld']]);
                }

                return $entity;
            },
        )->execute($jobExecution);
    }
}
