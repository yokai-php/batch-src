<?php

declare(strict_types=1);

namespace Yokai\Batch\Routing;

/**
 * This routing implementation uses a callback, component couple to determine matching component.
 * The callback must return truthy value in order to the component to match.
 *
 * @psalm-template T of object
 * @template-implements RoutingInterface<T>
 */
class CallbackRouting implements RoutingInterface
{
    /**
     * @phpstan-var list<array{0: callable, 1: T}>
     */
    private array $strategies;

    /**
     * @phpstan-var T
     */
    private object $default;

    /**
     * @phpstan-param list<array{0: callable, 1: T}> $strategies
     * @phpstan-param T $default
     */
    public function __construct(array $strategies, object $default)
    {
        $this->strategies = $strategies;
        $this->default = $default;
    }

    /**
     * @inheritdoc
     */
    public function get($subject)
    {
        foreach ($this->strategies as [$callback, $component]) {
            if ($callback($subject)) {
                return $component;
            }
        }

        return $this->default;
    }
}
