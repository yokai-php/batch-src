<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\TemplatingInterface;

/**
 * This compiler pass ensure that service behind {@see TemplatingInterface} is having the proper interface.
 */
final class ConfigureTemplatingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasAlias(TemplatingInterface::class)) {
            return;
        }

        $templatingActualService = (string)$container->getAlias(TemplatingInterface::class);

        try {
            $templatingService = $container->findDefinition($templatingActualService);
        } catch (ServiceNotFoundException $exception) {
            throw new LogicException(
                message: \sprintf(
                    'UI templating service "%s" does not exists.',
                    $templatingActualService,
                ),
                previous: $exception,
            );
        }

        $templatingServiceClass = (string)$templatingService->getClass();
        if (!\is_a($templatingServiceClass, TemplatingInterface::class, true)) {
            throw new LogicException(
                \sprintf(
                    'UI templating service "%s" must implements interface "%s".',
                    $templatingActualService,
                    TemplatingInterface::class,
                ),
            );
        }
    }
}
