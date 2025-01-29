<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty;

use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty\Episode;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty\Location;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty\LocationType;

/**
 * Rick and Morty {@see Episode} entity import.
 */
final class ImportRickAndMortyLocationJob implements JobInterface, JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'rick-and-morty.import:location';
    }

    public function __construct(
        private readonly ImportRickAndMortyJobFactory $factory,
        private readonly ImportRickAndMortyMemory $memory,
    ) {
    }

    public function execute(JobExecution $jobExecution): void
    {
        $this->factory->create(
            'https://rickandmortyapi.com/api/location',
            \glob(__DIR__ . '/../../../data/rick-and-morty/locations/*.json'),
            function (array $item) {
                $entity = new Location();
                $entity->name = $item['name'];
                $entity->type = LocationType::tryFrom((string)$item['type']) ?? LocationType::Unknown;
                $entity->dimension = $item['dimension'];

                $this->memory->add(Location::class, $item['url'], ['name' => $entity->name]);

                return $entity;
            },
        )->execute($jobExecution);
    }
}
