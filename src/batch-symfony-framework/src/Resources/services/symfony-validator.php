<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Bridge\Symfony\Validator\SkipInvalidItemProcessor;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(SkipInvalidItemProcessor::class)
            ->args([
                service(ValidatorInterface::class),
            ])
    ;
};
