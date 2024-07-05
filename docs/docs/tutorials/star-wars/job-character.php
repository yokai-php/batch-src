<?php

namespace App\Job\Import;

use App\Entity\Character;
use App\Entity\Planet;
use App\Entity\Specie;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

final class ImportStarWarsCharacterJob extends AbstractImportStartWarsEntityJob
{
    public static function getJobName(): string
    {
        return 'starwars_import-character';
    }

    public function __construct(
        ValidatorInterface $validator,
        ManagerRegistry $doctrine,
        JobExecutionStorageInterface $executionStorage,
    ) {
        parent::__construct(
            __DIR__ . '/path/to/star-wars/species.csv',
            function (array $item) use ($doctrine) {
                $entity = new Character();
                $entity->name = $item['name'];
                $entity->birthYear = $item['birth_year'] ? (int)$item['birth_year'] : null;
                $entity->gender = $item['gender'] ?? 'unknown';
                $entity->homeWorld = $doctrine->getRepository(Planet::class)
                    ->findOneBy(['name' => $item['homeworld']]);
                $entity->specie = $doctrine->getRepository(Specie::class)
                    ->findOneBy(['name' => $item['species']]);

                return $entity;
            },
            $validator,
            $doctrine,
            $executionStorage,
        );
    }
}
