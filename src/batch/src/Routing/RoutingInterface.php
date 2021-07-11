<?php

declare(strict_types=1);

namespace Yokai\Batch\Routing;

/**
 * A router is a component that is able to
 * filter out the appropriate subcomponent from a list of.
 *
 * @phpstan-template T of object
 */
interface RoutingInterface
{
    /**
     * @param mixed $subject
     *
     * @return mixed
     * @phpstan-return T
     */
    public function get($subject);
}
