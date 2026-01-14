<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Yokai\Batch\Bridge\Symfony\Messenger\LaunchJobMessageHandler;
use Yokai\Batch\Job\JobExecutionAccessor;
use Yokai\Batch\Job\JobExecutor;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(LaunchJobMessageHandler::class)
            ->args([
                service(JobExecutionAccessor::class),
                service(JobExecutor::class),
            ])
            ->tag('messenger.message_handler')
    ;
};
