<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Controller\JobController;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\JobSecurity;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\PaginationConfiguration;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\TemplatingInterface;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\TwigExtension;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(JobController::class)
            ->args([
                service(JobExecutionStorageInterface::class),
                service(FormFactoryInterface::class)->nullOnInvalid(),
                service(JobSecurity::class),
                service(Environment::class),
                service(TemplatingInterface::class),
                service(PaginationConfiguration::class),
            ])
            ->tag('controller.service_arguments')

        ->set(JobSecurity::class)
            ->args([
                service(AuthorizationCheckerInterface::class)->nullOnInvalid(),
                param('yokai_batch.ui.security_list_attribute'),
                param('yokai_batch.ui.security_view_attribute'),
                param('yokai_batch.ui.security_traces_attribute'),
                param('yokai_batch.ui.security_logs_attribute'),
            ])

        ->set(TwigExtension::class)
            ->args([
                service(JobSecurity::class),
            ])
            ->tag('twig.extension')
    ;
};
