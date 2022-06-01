<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\ORM\Dummy;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\ObjectManager;

final class SingleManagerRegistry extends AbstractManagerRegistry
{
    public function __construct(
        private ObjectManager $manager,
    ) {
        parent::__construct('ORM', ['default'], ['default'], 'default', 'default', Proxy::class);
    }

    protected function getService($name)
    {
        if ($name !== 'default') {
            throw new \InvalidArgumentException('Unknown service "' . $name . '".');
        }

        return $this->manager;
    }

    protected function resetService($name)
    {
    }

    public function getAliasNamespace($alias)
    {
        return $alias;
    }
}
