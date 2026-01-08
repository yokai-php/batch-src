<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty;

use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty\Episode;

/**
 * Rick and Morty {@see Episode} entity import.
 */
final readonly class ImportRickAndMortyEpisodeJob implements JobInterface, JobWithStaticNameInterface
{
    public static function getJobName(): string
    {
        return 'rick-and-morty.import:episode';
    }

    public function __construct(
        private ImportRickAndMortyJobFactory $factory,
        private ImportRickAndMortyMemory $memory,
    ) {
    }

    public function execute(JobExecution $jobExecution): void
    {
        $this->factory->create(
            'https://rickandmortyapi.com/api/episode',
            \glob(__DIR__ . '/../../../data/rick-and-morty/episodes/*.json'),
            function (array $item) {
                $entity = new Episode();
                $entity->code = $item['episode'];
                $entity->name = $item['name'];
                $entity->date = new \DateTimeImmutable($item['air_date']);

                $this->memory->add(Episode::class, $item['url'], ['code' => $entity->code]);

                return $entity;
            },
        )->execute($jobExecution);
    }
}
