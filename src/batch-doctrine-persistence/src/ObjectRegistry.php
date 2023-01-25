<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\Persistence;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\Persistence\ObjectManager;

final class ObjectRegistry
{
    /**
     * @var array<class-string, array<string, mixed[]>>
     */
    private array $identities = [];

    public function __construct(
        private ManagerRegistry $doctrine,
    ) {
    }

    /**
     * @template T
     *
     * @param class-string<T>      $class
     * @param array<string, mixed> $criteria
     *
     * @return T|null
     */
    public function findOneBy(string $class, array $criteria): ?object
    {
        return $this->findOneUsing(
            $class,
            fn(ObjectRepository $repository) => $repository->findOneBy($criteria),
            serialize($criteria)
        );
    }

    /**
     * @template T
     *
     * @param class-string<T>                                 $class
     * @param \Closure(ObjectRepository=, ObjectManager=): ?T $closure
     * @param string|null                                     $key
     *
     * @return T|null
     */
    public function findOneUsing(string $class, \Closure $closure, string $key = null): ?object
    {
        $manager = $this->doctrine->getManagerForClass($class);

        $key ??= spl_object_hash($closure);
        $key = md5($key);

        $identity = $this->identities[$class][$key] ?? null;
        if ($identity !== null) {
            return $manager->find($class, $identity);
        }

        $object = $closure($manager->getRepository($class), $manager);

        if (is_object($object)) {
            $this->identities[$class] ??= [];
            $this->identities[$class][$key] = $manager->getClassMetadata($class)->getIdentifierValues($object);
        }

        return $object;
    }

    public function reset(): void
    {
        $this->identities = [];
    }
}
