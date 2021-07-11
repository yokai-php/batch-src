<?php

declare(strict_types=1);

namespace Yokai\Batch\Routing;

/**
 * This routing implementation uses a class => component map
 * in conjonction with instanceof test to determine matching component.
 *
 * @psalm-template T
 * @template-extends CallbackRouting<T>
 */
final class ClassMapRouting extends CallbackRouting
{
    /**
     * @phpstan-param array<class-string, T>
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
