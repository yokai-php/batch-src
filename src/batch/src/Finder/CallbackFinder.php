<?php

declare(strict_types=1);

namespace Yokai\Batch\Finder;

/**
 * This finder implementation uses a callback, component couple to determine matching component.
 * The callback must return truthy value in order to the component to match.
 *
 * @psalm-template T of object
 * @template-implements FinderInterface<T>
 */
class CallbackFinder implements FinderInterface
{
    public function __construct(
        /**
         * @phpstan-var list<array{0: callable, 1: T}>
         */
        private array $strategies,
        /**
         * @phpstan-var T
         */
        private object $default,
    ) {
    }

    public function find(mixed $subject): object
    {
        foreach ($this->strategies as [$callback, $component]) {
            if ($callback($subject)) {
                return $component;
            }
        }

        return $this->default;
    }
}
