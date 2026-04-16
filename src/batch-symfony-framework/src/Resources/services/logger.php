<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\Persistence\ManagerRegistry;
use Yokai\Batch\Event\PostExecuteEvent;
use Yokai\Batch\Event\PreExecuteEvent;
use Yokai\Batch\Logger\YokaiBatchLogger;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(YokaiBatchLogger::class)
            ->args([
                service(ManagerRegistry::class),
            ])
            ->tag('kernel.event_listener', ['event' => PreExecuteEvent::class, 'method' => 'onPreExecute'])
            ->tag('kernel.event_listener', ['event' => PostExecuteEvent::class, 'method' => 'onPostExecute'])
    ;
};
