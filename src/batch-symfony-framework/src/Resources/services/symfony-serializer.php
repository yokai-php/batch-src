<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Serializer\SerializerInterface;
use Yokai\Batch\Bridge\Symfony\Serializer\NormalizeItemProcessor;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(NormalizeItemProcessor::class)
            ->args([
                service(SerializerInterface::class),
            ])
    ;
};
