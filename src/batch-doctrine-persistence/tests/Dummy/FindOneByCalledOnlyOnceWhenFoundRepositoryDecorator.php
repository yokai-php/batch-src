<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Dummy;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;

final class FindOneByCalledOnlyOnceWhenFoundRepositoryDecorator extends EntityRepository
{
    private array $calls = [];

    public function __construct(
        private readonly ObjectRepository $decorated,
    ) {
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, int|null $lockVersion = null): object|null
    {
        return $this->decorated->find($id);
    }

    public function findAll(): array
    {
        return $this->decorated->findAll();
    }

    public function findBy(
        array $criteria,
        array|null $orderBy = null,
        int|null $limit = null,
        int|null $offset = null,
    ): array {
        return $this->decorated->findBy($criteria, $orderBy, $limit, $offset);
    }

    public function findOneBy(array $criteria, array|null $orderBy = null): object|null
    {
        $result = $this->decorated->findOneBy($criteria);
        if ($result === null) {
            return null;
        }

        $this->ensureNotCalledAlready(__FUNCTION__, \func_get_args());

        return $result;
    }

    public function getClassName(): string
    {
        return $this->decorated->getClassName();
    }

    private function ensureNotCalledAlready(string $method, array $args): void
    {
        $key = \md5($method . $serializedArgs = \serialize($args));
        if (isset($this->calls[$key])) {
            throw new \LogicException(
                'Method ' . $method . ' with args ' . $serializedArgs . ' has already been called',
            );
        }

        $this->calls[$key] = true;
    }
}
