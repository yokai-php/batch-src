<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Yokai\Batch\Launcher\JobLauncherInterface;

/**
 * This compiler pass ensure that service behind {@see JobLauncherInterface} is having the proper interface.
 */
final class ConfigureLauncherPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $templatingActualService = (string)$container->getAlias(JobLauncherInterface::class);

        try {
            $launcherService = $container->findDefinition($templatingActualService);
        } catch (ServiceNotFoundException $exception) {
            throw new LogicException(
                message: \sprintf(
                    'Job launcher service "%s" does not exists.',
                    $templatingActualService,
                ),
                previous: $exception,
            );
        }

        $jobLauncherServiceClass = (string)$launcherService->getClass();
        if (!\is_a($jobLauncherServiceClass, JobLauncherInterface::class, true)) {
            throw new LogicException(
                \sprintf(
                    'Job launcher service "%s" must implements interface "%s".',
                    $templatingActualService,
                    JobLauncherInterface::class,
                ),
            );
        }
    }
}
