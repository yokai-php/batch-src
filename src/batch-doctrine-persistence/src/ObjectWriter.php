<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\Persistence;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Yokai\Batch\Exception\InvalidArgumentException;
use Yokai\Batch\Job\Item\ItemWriterInterface;

final class ObjectWriter implements ItemWriterInterface
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var ObjectManager[]
     */
    private $encounteredManagers = [];

    /**
     * @var ObjectManager[]
     */
    private $encounteredClasses = [];

    /**
     * @var ObjectManager[]
     */
    private $managerForClass = [];

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
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
        }

        foreach ($this->encounteredClasses as $class => $manager) {
            $manager->clear($class);
        }

        $this->encounteredManagers = [];
        $this->encounteredClasses = [];
    }

    private function getManagerForClass(object $item): ObjectManager
    {
        $class = get_class($item);

        $manager = $this->managerForClass[$class] ?? null;
        if ($manager === null) {
            $manager = $this->doctrine->getManagerForClass($class);
            if ($manager === null) {
                throw $this->createInvalidItemException($item);
            }

            $this->managerForClass[$class] = $manager;
        }

        $this->encounteredClasses[$class] = $manager;
        $this->encounteredManagers[spl_object_id($manager)] = $manager;

        return $manager;
    }

    /**
     * @param mixed $item
     *
     * @return InvalidArgumentException
     */
    private function createInvalidItemException($item): InvalidArgumentException
    {
        return new InvalidArgumentException(
            sprintf(
                'Items to write must be object managed by Doctrine. Got "%s".',
                is_object($item) ? get_class($item) : gettype($item)
            )
        );
    }
}
