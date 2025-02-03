<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars;

use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars\Planet;

/**
 * Star Wars {@see Planet} entity import.
 */
final class ImportStarWarsPlanetJob implements JobInterface, JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'star-wars.import:planet';
    }

    public function __construct(
        private readonly ImportStarWarsJobFactory $factory,
    ) {
    }

    public function execute(JobExecution $jobExecution): void
    {
        $this->factory->create(
            __DIR__ . '/../../../data/star-wars/planets.csv',
            function (array $item) {
                $entity = new Planet();
                $entity->name = $item['name'];
                $entity->rotationPeriod = $item['rotation_period'] ? (int)$item['rotation_period'] : null;
                $entity->orbitalPeriod = $item['orbital_period'] ? (int)$item['orbital_period'] : null;
                $entity->population = $item['population'] ? (int)$item['population'] : null;
                $entity->terrain = \array_filter(
                    \array_map('trim', \explode(',', (string)$item['terrain'])),
                );

                return $entity;
            },
        )->execute($jobExecution);
    }
}
