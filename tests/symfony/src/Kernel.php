<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\HttpKernel\Log\Logger;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Yokai\Batch\Bridge\Symfony\Framework\YokaiBatchBundle;
use Yokai\Batch\Job\JobInterface;

final class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new TwigBundle();
        yield new SecurityBundle();
        yield new YokaiBatchBundle();
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'ThisIsNotSecret',
            'test' => true,
            'default_locale' => 'en',
            'translator' => null,
            'csrf_protection' => true,
            'session' => [
                'handler_id' => null,
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
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
        $container->extension('twig', [
            'default_path' => __DIR__ . '/../templates',
            'form_themes' => ['bootstrap_4_layout.html.twig'],
        ]);
        $container->extension('yokai_batch', [
            'storage' => [
                'filesystem' => null,
            ],
            'ui' => [
                'enabled' => true,
            ],
        ]);

        $container->services()
            ->set('logger', Logger::class)
            ->args([null, '%kernel.logs_dir%/test.log', null, new Reference(RequestStack::class)])
            ->private();

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
        $routes->import('@YokaiBatchBundle/Resources/routing/ui.xml');
    }

    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('yokai_batch.job_launcher.simple')->setPublic(true);
    }
}
