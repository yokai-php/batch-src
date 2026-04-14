<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\Factory\JobExecutionIdGeneratorInterface;
use Yokai\Batch\Factory\JobExecutionLoggerFactory\InMemoryJobExecutionLoggerFactory;
use Yokai\Batch\Factory\JobExecutionLoggerFactoryInterface;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\ChainJobExecutionParametersBuilder;
use Yokai\Batch\Factory\JobExecutionParametersBuilderInterface;
use Yokai\Batch\Job\JobExecutionAccessor;
use Yokai\Batch\Job\JobExecutor;
use Yokai\Batch\Registry\JobRegistry;
use Yokai\Batch\Serializer\JsonJobExecutionSerializer;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(JobRegistry::class)

        ->set(JsonJobExecutionSerializer::class)
            ->args([
                service(JobExecutionLoggerFactoryInterface::class),
            ])

        ->set(JobExecutionLoggerFactoryInterface::class, InMemoryJobExecutionLoggerFactory::class)

        ->set(JobExecutionFactory::class)
            ->args([
                service(JobExecutionIdGeneratorInterface::class),
                service(JobExecutionParametersBuilderInterface::class),
                service(JobExecutionLoggerFactoryInterface::class),
            ])

        ->set(JobExecutionAccessor::class)
            ->args([
                service(JobExecutionFactory::class),
                service(JobExecutionStorageInterface::class),
            ])

        ->set(JobExecutor::class)
            ->args([
                service(JobRegistry::class),
                service(JobExecutionStorageInterface::class),
                service(EventDispatcherInterface::class)->nullOnInvalid(),
            ])

        ->set(JobExecutionParametersBuilderInterface::class, ChainJobExecutionParametersBuilder::class)
            ->args([
                tagged_iterator('yokai_batch.job_execution_parameters_builder'),
            ])
    ;
};
