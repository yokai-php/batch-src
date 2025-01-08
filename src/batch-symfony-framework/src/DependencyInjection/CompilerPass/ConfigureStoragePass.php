<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Yokai\Batch\Storage\JobExecutionStorageInterface;
use Yokai\Batch\Storage\ListableJobExecutionStorageInterface;
use Yokai\Batch\Storage\QueryableJobExecutionStorageInterface;

/**
 * This compiler pass ensure that service behind {@see JobExecutionStorageInterface} is having the proper interface.
 * Also, if that service implements some optional interfaces, we create some autowiring alias for these.
 */
final class ConfigureStoragePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $jobExecutionStorageActualService = (string)$container->getAlias(JobExecutionStorageInterface::class);

        try {
            $jobExecutionStorageService = $container->getDefinition($jobExecutionStorageActualService);
        } catch (ServiceNotFoundException $exception) {
            throw new LogicException(
                message: \sprintf(
                    'Job execution storage service "%s" does not exists.',
                    $jobExecutionStorageActualService,
                ),
                previous: $exception,
            );
        }

        $jobExecutionStorageServiceClass = (string)$jobExecutionStorageService->getClass();
        if (!\is_a($jobExecutionStorageServiceClass, JobExecutionStorageInterface::class, true)) {
            throw new LogicException(
                \sprintf(
                    'Job execution storage service "%s" must implements interface "%s".',
                    $jobExecutionStorageActualService,
                    JobExecutionStorageInterface::class,
                ),
            );
        }

        $optionalInterfaces = [
            ListableJobExecutionStorageInterface::class,
            QueryableJobExecutionStorageInterface::class,
        ];
        foreach ($optionalInterfaces as $interface) {
            if (\is_a($jobExecutionStorageServiceClass, $interface, true)) {
                $container
                    ->setAlias($interface, $jobExecutionStorageActualService)
                    ->setPublic(true)
                ;
            }
        }
    }
}
