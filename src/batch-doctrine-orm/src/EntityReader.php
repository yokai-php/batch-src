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
    public function __construct(
        private ManagerRegistry $doctrine,
        /**
         * @var class-string
         */
        private string $class,
    ) {
    }

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

        yield from $manager->createQueryBuilder()
            ->select('e')
            ->from($this->class, 'e')
            ->getQuery()
            ->toIterable();
    }
}
