<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Item\ItemReaderInterface;

/**
 * This {@see ItemReaderInterface} executes an SQL query to a Doctrine connection,
 */
final class EntityReader implements ItemReaderInterface
{
    private ManagerRegistry $doctrine;
    private string $class;

    public function __construct(ManagerRegistry $doctrine, string $class)
    {
        $this->doctrine = $doctrine;
        $this->class = $class;
    }

    /**
     * @inheritDoc
     */
    public function read(): iterable
    {
        $manager = $this->doctrine->getManagerForClass($this->class);
        if (!$manager instanceof EntityManagerInterface) {
            throw UnexpectedValueException::type(
                EntityManagerInterface::class,
                $manager,
                'Provided class must be a valid Doctrine entity.'
            );
        }

        $query = $manager->createQueryBuilder()
            ->select('e')
            ->from($this->class, 'e');

        foreach ($query->getQuery()->toIterable() as [$entity]) {
            yield $entity;
        }
    }
}
