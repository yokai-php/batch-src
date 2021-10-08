<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars\Planet;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * Star Wars {@see Planet} entity import.
 * See {@see AbstractImportStartWarsEntityJob} parent class for more details.
 */
final class ImportStarWarsPlanetJob extends AbstractImportStartWarsEntityJob
{
    public static function getJobName(): string
    {
        return 'star-wars.import:planet';
    }

    public function __construct(
        KernelInterface $kernel,
        ValidatorInterface $validator,
        ManagerRegistry $doctrine,
        JobExecutionStorageInterface $executionStorage
    ) {
        parent::__construct(
            $kernel->getProjectDir() . '/data/star-wars/planets.csv',
            function (array $item) {
                $entity = new Planet();
                $entity->name = $item['name'];

                return $entity;
            },
            $validator,
            $doctrine,
            $executionStorage
        );
    }
}
