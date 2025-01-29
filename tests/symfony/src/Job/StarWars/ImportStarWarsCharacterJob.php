<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars;

use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectRegistry;
use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars\Character;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars\Planet;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars\Specie;

/**
 * Star Wars {@see Character} entity import.
 */
final class ImportStarWarsCharacterJob implements JobInterface, JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'star-wars.import:character';
    }

    public function __construct(
        private readonly ImportStarWarsJobFactory $factory,
    ) {
    }

    public function execute(JobExecution $jobExecution): void
    {
        $this->factory->create(
            __DIR__ . '/../../../data/star-wars/characters.csv',
            function (array $item, ObjectRegistry $objectRegistry) {
                $entity = new Character();
                $entity->name = $item['name'];
                $entity->birthYear = $item['birth_year'] ? (int)$item['birth_year'] : null;
                $entity->gender = $item['gender'] ?? 'unknown';
                $entity->homeWorld = $objectRegistry->findOneBy(Planet::class, ['name' => $item['homeworld']]);
                $entity->specie = $objectRegistry->findOneBy(Specie::class, ['name' => $item['species']]);

                return $entity;
            },
        )->execute($jobExecution);
    }
}
