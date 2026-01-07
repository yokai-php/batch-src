<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Controller\JobController;

return function (RoutingConfigurator $routes): void {
    $routes->add('yokai_batch.job_list', '/jobs')
        ->controller([JobController::class, 'list'])
        ->methods(['GET'])
    ;
    $routes->add('yokai_batch.job_view', '/jobs/{job}/{id}')
        ->controller([JobController::class, 'view'])
        ->methods(['GET'])
    ;
    $routes->add('yokai_batch.job_view_child', '/jobs/{job}/{id}/child/{path}')
        ->controller([JobController::class, 'view'])
        ->methods(['GET'])
    ;
    $routes->add('yokai_batch.job_logs', '/jobs/{job}/{id}/logs')
        ->controller([JobController::class, 'logs'])
        ->methods(['GET'])
    ;
};
