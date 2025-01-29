<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty;

use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty\Character;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty\CharacterGender;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty\CharacterSpecie;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty\CharacterStatus;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty\Episode;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty\Location;

/**
 * Rick and Morty {@see Character} entity import.
 */
final class ImportRickAndMortyCharacterJob implements JobInterface, JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'rick-and-morty.import:character';
    }

    public function __construct(
        private readonly ImportRickAndMortyJobFactory $factory,
    ) {
    }

    public function execute(JobExecution $jobExecution): void
    {
        $this->factory->create(
            'https://rickandmortyapi.com/api/character',
            \glob(__DIR__ . '/../../../data/rick-and-morty/characters/*.json'),
            function (array $item, ImportRickAndMortyMemory $memory) {
                $entity = new Character();
                $entity->name = $item['name'];
                $entity->status = CharacterStatus::tryFrom((string)$item['status']) ?? CharacterStatus::Unknown;
                $entity->specie = CharacterSpecie::tryFrom((string)$item['species']) ?? CharacterSpecie::Unknown;
                $entity->gender = CharacterGender::tryFrom((string)$item['gender']) ?? CharacterGender::Unknown;
                $entity->description = $item['type'];
                $entity->origin = $memory->get(Location::class, $item['origin']['url']);
                $entity->location = $memory->get(Location::class, $item['location']['url']);
                foreach ($item['episode'] as $episode) {
                    $entity->episodes->add($memory->get(Episode::class, $episode));
                }

                return $entity;
            },
        )->execute($jobExecution);
    }
}
