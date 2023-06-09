<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Generator;
use PHPUnit\Framework\Assert;
use Psr\Container\ContainerInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars\ImportStarWarsCharacterJob;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars\ImportStarWarsJob;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars\ImportStarWarsPlanetJob;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars\ImportStarWarsSpecieJob;

final class StarWarsJobSet
{
    public static function sets(): Generator
    {
        yield [
            ImportStarWarsJob::getJobName(),
            static function (JobExecution $execution, ContainerInterface $container) {
                /** @var Connection $connection */
                $connection = $container->get('doctrine.dbal.default_connection');

                $count = fn(string $table) => (int)$connection->executeQuery("SELECT COUNT(*) FROM $table;")
                    ->fetchFirstColumn()[0];

                JobAssert::assertIsSuccessful($execution);

                $planetsExecution = $execution->getChildExecution(ImportStarWarsPlanetJob::getJobName());
                JobAssert::assertItemJobStats($planetsExecution, 61, 60, 60, 1);
                Assert::assertSame(60, $count('star_wars_planet'));

                $speciesExecution = $execution->getChildExecution(ImportStarWarsSpecieJob::getJobName());
                JobAssert::assertItemJobStats($speciesExecution, 37, 37, 37);
                Assert::assertSame(37, $count('star_wars_specie'));

                $charactersExecution = $execution->getChildExecution(ImportStarWarsCharacterJob::getJobName());
                JobAssert::assertItemJobStats($charactersExecution, 87, 87, 87);
                Assert::assertSame(87, $count('star_wars_character'));

                $results = $connection->executeQuery(
                    <<<SQL
                    SELECT character.name as name,
                           homeworld.name as homeworld,
                           specie.name as species
                    FROM star_wars_character as character
                    LEFT JOIN star_wars_planet homeworld on homeworld.id = character.home_world_id
                    LEFT JOIN star_wars_specie specie on specie.id = character.specie_id
                    SQL
                )->fetchAllAssociative();

                Assert::assertContains(
                    [
                        'name' => 'Luke Skywalker',
                        'homeworld' => 'Tatooine',
                        'species' => 'Human',
                    ],
                    $results
                );
                Assert::assertContains(
                    [
                        'name' => 'Captain Phasma',
                        'homeworld' => null,
                        'species' => null,
                    ],
                    $results
                );
            },
            static function (ContainerInterface $container) {
                /** @var EntityManagerInterface $entityManager */
                $entityManager = $container->get('doctrine.orm.default_entity_manager');
                $connection = $entityManager->getConnection();

                $database = $connection->getParams()['path'];
                if (file_exists($database)) {
                    unlink($database);
                }
                $schema = $connection->createSchemaManager();
                $schema->createDatabase($database);

                (new SchemaTool($entityManager))
                    ->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
            },
        ];
    }
}
