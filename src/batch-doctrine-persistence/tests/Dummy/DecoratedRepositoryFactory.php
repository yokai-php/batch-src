<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Dummy;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\ObjectRepository;

class DecoratedRepositoryFactory implements RepositoryFactory
{
    public function __construct(
        /**
         * @var class-string<ObjectRepository>
         */
        private string $class,
        private RepositoryFactory $decorated,
    ) {
    }

    public function getRepository(EntityManagerInterface $entityManager, $entityName): ObjectRepository
    {
        return new $this->class($this->decorated->getRepository($entityManager, $entityName));
    }
}
