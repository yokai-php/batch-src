<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\Persistence\ManagerRegistry;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectRegistry;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectWriter;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(ObjectWriter::class)
            ->args([
                service(ManagerRegistry::class),
            ])

        ->set(ObjectRegistry::class)
            ->args([
                service(ManagerRegistry::class),
            ])
    ;
};
