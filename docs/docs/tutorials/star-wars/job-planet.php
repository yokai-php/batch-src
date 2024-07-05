<?php

namespace App\Job\Import;

use App\Entity\Planet;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

final class ImportStarWarsPlanetJob extends AbstractImportStartWarsEntityJob
{
    public static function getJobName(): string
    {
        return 'starwars_import-planet';
    }

    public function __construct(
        ValidatorInterface $validator,
        ManagerRegistry $doctrine,
        JobExecutionStorageInterface $executionStorage,
    ) {
        parent::__construct(
            __DIR__ . '/path/to/star-wars/planets.csv',
            function (array $item) {
                $entity = new Planet();
                $entity->name = $item['name'];
                $entity->rotationPeriod = $item['rotation_period'] ? (int)$item['rotation_period'] : null;
                $entity->orbitalPeriod = $item['orbital_period'] ? (int)$item['orbital_period'] : null;
                $entity->population = $item['population'] ? (int)$item['population'] : null;
                $entity->terrain = \array_filter(
                    \array_map('trim', \explode(',', (string)$item['terrain']))
                );

                return $entity;
            },
            $validator,
            $doctrine,
            $executionStorage,
        );
    }
}
