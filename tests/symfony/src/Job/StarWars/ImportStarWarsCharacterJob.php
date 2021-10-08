<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars\Character;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars\Planet;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars\Specie;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * Star Wars {@see Character} entity import.
 * See {@see AbstractImportStartWarsEntityJob} parent class for more details.
 */
final class ImportStarWarsCharacterJob extends AbstractImportStartWarsEntityJob
{
    public static function getJobName(): string
    {
        return 'star-wars.import:character';
    }

    public function __construct(
        KernelInterface $kernel,
        ValidatorInterface $validator,
        ManagerRegistry $doctrine,
        JobExecutionStorageInterface $executionStorage
    ) {
        parent::__construct(
            $kernel->getProjectDir() . '/data/star-wars/characters.csv',
            function (array $item) use ($doctrine) {
                $entity = new Character();
                $entity->name = $item['name'];
                $entity->birthYear = (int)$item['birth_year'];
                $entity->gender = $item['gender'] ?? 'unknown';
                $entity->homeWorld = $doctrine->getRepository(Planet::class)
                    ->findOneBy(['name' => $item['homeworld']]);
                $entity->specie = $doctrine->getRepository(Specie::class)
                    ->findOneBy(['name' => $item['species']]);

                return $entity;
            },
            $validator,
            $doctrine,
            $executionStorage
        );
    }
}
