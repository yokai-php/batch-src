<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Dummy;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Repository\RepositoryFactory;

class DecoratedRepositoryFactory implements RepositoryFactory
{
    /**
     * @var array<string, EntityRepository>
     */
    private array $repositories = [];

    public function __construct(
        /**
         * @var class-string<EntityRepository>
         */
        private string $class,
        private RepositoryFactory $decorated,
    ) {
    }

    public function getRepository(EntityManagerInterface $entityManager, string $entityName): EntityRepository
    {
        return $this->repositories[$entityName] ??= new $this->class(
            $this->decorated->getRepository($entityManager, $entityName),
        );
    }
}
