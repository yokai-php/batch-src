<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty;

use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectRegistry;

final class ImportRickAndMortyMemory
{
    /**
     * @var array<class-string, array<string, array<string, mixed>>>
     */
    private array $memory = [];

    public function __construct(
        private readonly ObjectRegistry $objectRegistry,
    ) {
    }

    /**
     * @param class-string         $class
     * @param array<string, mixed> $criteria
     */
    public function add(string $class, string $url, array $criteria): void
    {
        $this->memory[$class][$url] = $criteria;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    public function get(string $class, string $url): object|null
    {
        $criteria = $this->memory[$class][$url] ?? null;
        if ($criteria === null) {
            return null;
        }

        return $this->objectRegistry->findOneBy($class, $criteria);
    }
}
