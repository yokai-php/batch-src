<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Yokai\Batch\Bridge\Symfony\Console\RunJobCommand;
use Yokai\Batch\Bridge\Symfony\Console\SetupStorageCommand;
use Yokai\Batch\Job\JobExecutionAccessor;
use Yokai\Batch\Job\JobExecutor;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(RunJobCommand::class)
            ->args([
                service(JobExecutionAccessor::class),
                service(JobExecutor::class),
            ])
            ->tag('console.command')

        ->set(SetupStorageCommand::class)
            ->args([
                service(JobExecutionStorageInterface::class),
            ])
            ->tag('console.command')
    ;
};
