<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Dummy;

use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\Proxy;

final class SimpleManagerRegistry extends AbstractManagerRegistry
{
    public function __construct(
        /**
         * @var array<string, ObjectManager>
         */
        private array $services,
    ) {
        $connections = [];
        $managers = [];
        $defaultConnection = null;
        $defaultEntityManager = null;
        foreach ($this->services as $id => $service) {
            $connections[] = $id;
            $managers[] = $id;
            $defaultConnection ??= $id;
            $defaultEntityManager ??= $id;
        }
        parent::__construct(
            'ORM',
            $connections,
            $managers,
            $defaultConnection ?? 'unknown',
            $defaultEntityManager ?? 'unknown',
            Proxy::class,
        );
    }

    protected function getService(string $name): object
    {
        return $this->services[$name] ?? throw new \InvalidArgumentException('Unknown service "' . $name . '".');
    }

    protected function resetService(string $name): void
    {
    }

    public function getManagerForClass(string $class): ObjectManager|null
    {
        foreach ($this->services as $service) {
            foreach ($service->getMetadataFactory()->getAllMetadata() as $metadata) {
                if (\is_a($class, $metadata->name, true)) {
                    return $service;
                }
            }
        }

        return null;
    }
}
