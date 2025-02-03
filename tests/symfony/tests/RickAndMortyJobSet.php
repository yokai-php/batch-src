<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\Tests;

use Doctrine\DBAL\Connection;
use Generator;
use PHPUnit\Framework\Assert;
use Psr\Container\ContainerInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty\ImportRickAndMortyCharacterJob;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty\ImportRickAndMortyEpisodeJob;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty\ImportRickAndMortyJob;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty\ImportRickAndMortyLocationJob;

final class RickAndMortyJobSet
{
    public static function sets(): Generator
    {
        yield 'Rick and Morty Import' => [
            ImportRickAndMortyJob::getJobName(),
            static function (JobExecution $execution, ContainerInterface $container) {
                /** @var Connection $connection */
                $connection = $container->get('doctrine.dbal.default_connection');

                $count = fn(string $table) => (int)$connection->executeQuery("SELECT COUNT(*) FROM $table;")
                    ->fetchFirstColumn()[0];

                JobAssert::assertIsSuccessful($execution);

                $episodesExecution = $execution->getChildExecution(ImportRickAndMortyEpisodeJob::getJobName());
                JobAssert::assertItemJobStats($episodesExecution, 51, 51, 51);
                Assert::assertSame(51, $count('rick_and_morty_episode'));

                $locationsExecution = $execution->getChildExecution(ImportRickAndMortyLocationJob::getJobName());
                JobAssert::assertItemJobStats($locationsExecution, 126, 126, 126);
                Assert::assertSame(126, $count('rick_and_morty_location'));

                $charactersExecution = $execution->getChildExecution(ImportRickAndMortyCharacterJob::getJobName());
                JobAssert::assertItemJobStats($charactersExecution, 826, 796, 796, 30);
                Assert::assertSame(796, $count('rick_and_morty_character'));

                $results = $connection->executeQuery(
                    <<<SQL
                    SELECT character.name as name,
                           character.status as status,
                           character.specie as specie,
                           character.gender as gender,
                           origin.name as origin,
                           location.name as location
                    FROM rick_and_morty_character as character
                    LEFT JOIN rick_and_morty_location origin on origin.id = character.origin_id
                    LEFT JOIN rick_and_morty_location location on location.id = character.location_id
                    SQL,
                )->fetchAllAssociative();

                Assert::assertContains(
                    [
                        'name' => 'Rick Sanchez',
                        'status' => 'Alive',
                        'specie' => 'Human',
                        'gender' => 'Male',
                        'origin' => 'Earth (C-137)',
                        'location' => 'Citadel of Ricks',
                    ],
                    $results,
                );
                Assert::assertContains(
                    [
                        'name' => 'Evil Morty',
                        'status' => 'Alive',
                        'specie' => 'Human',
                        'gender' => 'Male',
                        'origin' => null,
                        'location' => 'Citadel of Ricks',
                    ],
                    $results,
                );
            },
        ];
    }
}
