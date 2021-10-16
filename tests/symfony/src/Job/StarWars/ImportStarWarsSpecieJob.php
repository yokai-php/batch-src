<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars\Planet;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars\Specie;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * Star Wars {@see Specie} entity import.
 * See {@see AbstractImportStartWarsEntityJob} parent class for more details.
 */
final class ImportStarWarsSpecieJob extends AbstractImportStartWarsEntityJob
{
    public static function getJobName(): string
    {
        return 'star-wars.import:specie';
    }

    public function __construct(
        KernelInterface $kernel,
        ValidatorInterface $validator,
        ManagerRegistry $doctrine,
        JobExecutionStorageInterface $executionStorage
    ) {
        parent::__construct(
            $kernel->getProjectDir() . '/data/star-wars/species.csv',
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
            $executionStorage
        );
    }
}
