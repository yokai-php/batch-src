<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Dummy;

use Doctrine\Persistence\ObjectRepository;

class FindOneByCalledOnlyOnceWhenFoundRepositoryDecorator implements ObjectRepository
{
    private array $calls = [];

    public function __construct(
        private ObjectRepository $decorated,
    ) {
    }

    public function find($id)
    {
        return $this->decorated->find($id);
    }

    public function findAll()
    {
        return $this->decorated->findAll();
    }

    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->decorated->findBy($criteria, $orderBy, $limit, $offset);
    }

    public function findOneBy(array $criteria)
    {
        $result = $this->decorated->findOneBy($criteria);
        if ($result === null) {
            return null;
        }

        $this->ensureNotCalledAlready(__FUNCTION__, \func_get_args());

        return $result;
    }

    public function getClassName()
    {
        return $this->decorated->getClassName();
    }

    private function ensureNotCalledAlready(string $method, array $args): void
    {
        $key = \md5($method . $serializedArgs = \serialize($args));
        if (isset($this->calls[$key])) {
            throw new \LogicException(
                'Method ' . $method . ' with args ' . $serializedArgs . ' has already been called'
            );
        }

        $this->calls[$key] = true;
    }
}
