<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Yokai\Batch\Exception\CannotAccessParameterException;
use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;
use Yokai\Batch\JobExecution;

/**
 * This job parameter accessor implementation returns container parameter value.
 */
final class ContainerParameterAccessor implements JobParameterAccessorInterface
{
    private ContainerInterface $container;
    private string $name;

    public function __construct(ContainerInterface $container, string $name)
    {
        $this->container = $container;
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public function get(JobExecution $execution)
    {
        try {
            return $this->container->getParameter($this->name);
        } catch (InvalidArgumentException $exception) {
            throw new CannotAccessParameterException(
                \sprintf('Cannot access "%s" parameter from container parameters', $this->name),
                $exception
            );
        }
    }
}
