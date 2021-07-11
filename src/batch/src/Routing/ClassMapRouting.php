<?php

declare(strict_types=1);

namespace Yokai\Batch\Routing;

/**
 * This routing implementation uses a class => component map
 * in conjonction with instanceof test to determine matching component.
 *
 * @psalm-template T of object
 * @template-extends CallbackRouting<T>
 */
class ClassMapRouting extends CallbackRouting
{
    /**
     * @phpstan-param array<class-string, T> $classMap
     * @phpstan-param T $default
     */
    public function __construct(array $classMap, object $default)
    {
        $strategies = [];
        foreach ($classMap as $class => $component) {
            $strategies[] = [fn($item) => \is_object($item) && $item instanceof $class, $component];
        }
        parent::__construct($strategies, $default);
    }
}
