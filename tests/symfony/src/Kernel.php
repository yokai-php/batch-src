<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Yokai\Batch\Bridge\Symfony\Framework\YokaiBatchBundle;
use Yokai\Batch\Job\JobInterface;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new YokaiBatchBundle();
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => true,
        ]);
        $container->extension('doctrine', [
            'dbal' => [
                'url' => 'sqlite:///%kernel.project_dir%/var/database.sqlite',
                'logging' => false,
            ],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore',
                'mappings' => [
                    'App' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => __DIR__ . '/Entity',
                        'prefix' => __NAMESPACE__ . '\\Entity',
                        'alias' => 'App',
                    ],
                ],
            ],
        ]);
        $container->extension('yokai_batch', [
            'storage' => [
                'filesystem' => null,
            ],
        ]);

        $container->services()
            ->defaults()
            ->autoconfigure(true)
            ->autowire(true)
            ->instanceof(JobInterface::class)
            ->tag('yokai_batch.job')
            ->load(__NAMESPACE__ . '\\', __DIR__)
            ->exclude(__DIR__ . '/{Entity,Kernel.php}');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
    }
}
