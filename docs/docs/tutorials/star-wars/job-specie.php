<?php

namespace App\Job\Import;

use App\Entity\Specie;
use App\Entity\Planet;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

final class ImportStarWarsSpecieJob extends AbstractImportStartWarsEntityJob
{
    public static function getJobName(): string
    {
        return 'starwars_import-specie';
    }

    public function __construct(
        ValidatorInterface $validator,
        ManagerRegistry $doctrine,
        JobExecutionStorageInterface $executionStorage,
    ) {
        parent::__construct(
            __DIR__ . '/path/to/star-wars/species.csv',
            function (array $item) use ($doctrine) {
                $entity = new Specie();
                $entity->name = $item['name'];
                $entity->classification = $item['classification'];
                $entity->language = $item['language'];
                if ($item['homeworld']) {
                    $entity->homeWorld = $doctrine->getRepository(Planet::class)
                        ->findOneBy(['name' => $item['homeworld']]);
                }

                return $entity;
            },
            $validator,
            $doctrine,
            $executionStorage,
        );
    }
}
