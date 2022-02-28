<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\Persistence;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Yokai\Batch\Exception\InvalidArgumentException;
use Yokai\Batch\Job\Item\ItemWriterInterface;

/**
 * This {@see ItemWriterInterface} will persist and flush all items,
 * via a Doctrine {@see ObjectManager}.
 */
final class ObjectWriter implements ItemWriterInterface
{
    /**
     * @var ObjectManager[]
     */
    private array $encounteredManagers = [];

    /**
     * @var ObjectManager[]
     */
    private array $managerForClass = [];

    public function __construct(
        private ManagerRegistry $doctrine,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function write(iterable $items): void
    {
        foreach ($items as $item) {
            if (!is_object($item)) {
                throw $this->createInvalidItemException($item);
            }

            $this->getManagerForClass($item)->persist($item);
        }

        foreach ($this->encounteredManagers as $manager) {
            $manager->flush();
            $manager->clear();
        }

        $this->encounteredManagers = [];
    }

    private function getManagerForClass(object $item): ObjectManager
    {
        $class = $item::class;

        $manager = $this->managerForClass[$class] ?? null;
        if ($manager === null) {
            $manager = $this->doctrine->getManagerForClass($class);
            if ($manager === null) {
                throw $this->createInvalidItemException($item);
            }

            $this->managerForClass[$class] = $manager;
        }

        $this->encounteredManagers[spl_object_id($manager)] = $manager;

        return $manager;
    }

    private function createInvalidItemException(mixed $item): InvalidArgumentException
    {
        return new InvalidArgumentException(
            sprintf('Items to write must be object managed by Doctrine. Got "%s".', get_debug_type($item))
        );
    }
}
