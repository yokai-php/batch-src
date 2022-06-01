<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Dummy;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\ObjectManager;

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
            Proxy::class
        );
    }

    protected function getService($name)
    {
        return $this->services[$name] ?? throw new \InvalidArgumentException('Unknown service "' . $name . '".');
    }

    protected function resetService($name)
    {
    }

    public function getAliasNamespace($alias)
    {
        return $alias;
    }
}
