<?php

declare(strict_types=1);

namespace Yokai\Batch\Routing;

/**
 * This routing implementation uses a callback, component couple to determine matching component.
 * The callback must return truthy value in order to the component to match.
 *
 * @psalm-template T
 * @template-implements RoutingInterface<T>
 */
class CallbackRouting implements RoutingInterface
{
    private array $strategies;
    private object $default;

    /**
     * @phpstan-param array{0: callable, 1: T}
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
