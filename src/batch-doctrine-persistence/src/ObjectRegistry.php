<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\Persistence;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\Persistence\ObjectManager;
use Yokai\Batch\Exception\InvalidArgumentException;

/**
 * This class will remember objects identifies when found.
 * Using it as a proxy to your queries will simplify queries afterward.
 */
final class ObjectRegistry
{
    /**
     * @var array<class-string, array<string, array<string, mixed>>>
     */
    private array $identities = [];

    public function __construct(
        private ManagerRegistry $doctrine,
    ) {
    }

    /**
     * Finds a single object by a set of criteria.
     *
     * @template T of object
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
            function ($repository) use ($criteria) {
                /** @var ObjectRepository<T> $repository */

                return $repository->findOneBy($criteria);
            },
            serialize($criteria)
        );
    }

    /**
     * Finds a single object by using a callback to find it.
     *
     * @template T of object
     *
     * @param class-string<T>                                        $class
     * @param \Closure(ObjectRepository<T>, ObjectManager): (T|null) $closure
     * @param string|null                                            $key
     *
     * @return T|null
     */
    public function findOneUsing(string $class, \Closure $closure, string $key = null): ?object
    {
        $manager = $this->doctrine->getManagerForClass($class);
        if ($manager === null) {
            throw new InvalidArgumentException(sprintf('Class "%s" is not a managed Doctrine entity.', $class));
        }

        $key ??= spl_object_hash($closure);
        $key = md5($key);

        $identity = $this->identities[$class][$key] ?? null;
        if ($identity !== null) {
            return $manager->find($class, $identity);
        }

        $object = $closure($manager->getRepository($class), $manager);

        if ($object instanceof $class) {
            $this->identities[$class] ??= [];
            $this->identities[$class][$key] = $manager->getClassMetadata($class)->getIdentifierValues($object);
        }

        return $object;
    }

    /**
     * Removes all remembered identities of all classes.
     */
    public function reset(): void
    {
        $this->identities = [];
    }
}
