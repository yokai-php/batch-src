<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Yokai\Batch\Job\JobInterface;

return function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
            ->autoconfigure(true)
            ->autowire(true)

        ->instanceof(JobInterface::class)
            ->tag('yokai_batch.job')

        ->load('Yokai\\Batch\\Sources\\Tests\\Symfony\\App\\', __DIR__.'/../src')
            ->exclude(__DIR__ . '/../src/{Entity,Kernel.php}')
    ;
};
