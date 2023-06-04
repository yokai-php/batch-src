<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Registry\JobRegistry;

/**
 * Find tagged {@see JobInterface} and register these in {@see JobRegistry}.
 */
final class RegisterJobsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $jobs = [];
        foreach ($container->findTaggedServiceIds('yokai_batch.job') as $serviceId => $tags) {
            $serviceDefinition = $container->findDefinition($serviceId);
            foreach ($tags as $attributes) {
                $jobs[$this->getJobName($serviceId, $serviceDefinition, $attributes)] = new Reference($serviceId);
            }
        }

        $container->getDefinition('yokai_batch.job_registry')
            ->setArgument('$jobs', ServiceLocatorTagPass::register($container, $jobs));
    }

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    private function getJobName(string $id, Definition $definition, array $attributes): string
    {
        if (isset($attributes['job']) && \is_string($attributes['job'])) {
            return $attributes['job'];
        }

        $serviceClass = $definition->getClass();
        if ($serviceClass !== null && \is_a($serviceClass, JobWithStaticNameInterface::class, true)) {
            return $serviceClass::getJobName();
        }

        return $id;
    }
}
