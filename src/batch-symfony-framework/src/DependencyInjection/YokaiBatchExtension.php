<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection;

use Composer\InstalledVersions;
use Psr\Log\LoggerInterface;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader as ConfigLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Loader as DependencyInjectionLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALJobExecutionStorage;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Form\JobFilterType;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\ConfigurableTemplating;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\SonataAdminTemplating;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\TemplatingInterface;
use Yokai\Batch\Factory\JobExecutionIdGeneratorInterface;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\PerJobJobExecutionParametersBuilder;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\StaticJobExecutionParametersBuilder;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Storage\FilesystemJobExecutionStorage;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * Dependency injection extension for yokai/batch Symfony Bundle.
 *
 * @phpstan-import-type Config from Configuration
 * @phpstan-import-type StorageConfig from Configuration
 * @phpstan-import-type LauncherConfig from Configuration
 * @phpstan-import-type ParametersConfig from Configuration
 * @phpstan-import-type UserInterfaceConfig from Configuration
 */
final class YokaiBatchExtension extends Extension
{
    /**
     * @param list<array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        /** @var Config $config */
        $config = $this->processConfiguration($configuration, $configs);

        $loader = $this->getLoader($container);
        $loader->load('global/');

        $bridges = [
            'doctrine/orm/' => $this->installed('doctrine-orm'),
            'symfony/console/' => $this->installed('symfony-console'),
            'symfony/messenger/' => $this->installed('symfony-messenger'),
            'symfony/serializer/' => $this->installed('symfony-serializer'),
            'symfony/validator/' => $this->installed('symfony-validator'),
        ];

        foreach (\array_keys(\array_filter($bridges)) as $resource) {
            $loader->load($resource);
        }

        $this->configureStorage($container, $config['storage']);
        $this->configureLauncher($container, $config['launcher']);
        $this->configureParameters($container, $config['parameters']);
        $this->configureUserInterface($container, $loader, $config['ui']);

        $jobExecutionIdGeneratorDefinition = JobExecutionIdGeneratorDefinitionFactory::fromType($config['id']);
        $container->setDefinition(JobExecutionIdGeneratorInterface::class, $jobExecutionIdGeneratorDefinition);

        $container->registerAliasForArgument('yokai_batch.logger', LoggerInterface::class, 'yokaiBatchLogger');
    }

    private function installed(string $package): bool
    {
        return InstalledVersions::isInstalled('yokai/batch-src')
            || InstalledVersions::isInstalled('yokai/batch-' . $package);
    }

    private function getLoader(ContainerBuilder $container): LoaderInterface
    {
        $locator = new FileLocator(__DIR__ . '/../Resources/services');
        $resolver = new ConfigLoader\LoaderResolver(
            [
                new DependencyInjectionLoader\XmlFileLoader($container, $locator),
                new DependencyInjectionLoader\DirectoryLoader($container, $locator),
            ]
        );

        return new ConfigLoader\DelegatingLoader($resolver);
    }

    /**
     * @param StorageConfig $config
     */
    private function configureStorage(ContainerBuilder $container, array $config): void
    {
        if (isset($config['service'])) {
            $defaultStorage = $config['service'];
        } elseif (isset($config['dbal'])) {
            $container
                ->register('yokai_batch.storage.dbal', DoctrineDBALJobExecutionStorage::class)
                ->setArguments(
                    [
                        new Reference('doctrine'),
                        [
                            'connection' => $config['dbal']['connection'],
                            'table' => $config['dbal']['table'],
                        ],
                    ]
                )
            ;

            $defaultStorage = 'yokai_batch.storage.dbal';
        } else {
            $container
                ->register('yokai_batch.storage.filesystem', FilesystemJobExecutionStorage::class)
                ->setArguments([new Reference($config['filesystem']['serializer']), $config['filesystem']['dir']])
            ;

            $defaultStorage = 'yokai_batch.storage.filesystem';
        }

        $container
            ->setAlias(JobExecutionStorageInterface::class, $defaultStorage)
            ->setPublic(true)
        ;
    }

    /**
     * @param LauncherConfig $config
     */
    private function configureLauncher(ContainerBuilder $container, array $config): void
    {
        if (!isset($config['launchers'][$config['default']])) {
            throw new LogicException(\sprintf(
                "Default job launcher \"%s\" was not registered in launchers config. Available launchers are %s.",
                $config['default'],
                \json_encode(\array_keys($config['launchers']), flags: \JSON_THROW_ON_ERROR),
            ));
        }

        $launcherIdPerLauncherName = [];
        foreach ($config['launchers'] as $name => $dsn) {
            $definitionOrReference = JobLauncherDefinitionFactory::fromDsn($dsn);
            if ($definitionOrReference instanceof Definition) {
                $launcherId = 'yokai_batch.job_launcher.' . $name;
                $container->setDefinition($launcherId, $definitionOrReference);
            } else {
                $launcherId = (string)$definitionOrReference;
            }

            $launcherIdPerLauncherName[$name] = $launcherId;
            $parameterName = $name . 'JobLauncher';
            $container->registerAliasForArgument($launcherId, LoggerInterface::class, $parameterName);
        }

        $container->setAlias(
            JobLauncherInterface::class,
            $launcherIdPerLauncherName[$config['default']],
        );
    }

    /**
     * @param ParametersConfig $config
     */
    private function configureParameters(ContainerBuilder $container, array $config): void
    {
        if ($config['global'] !== []) {
            $container->register('yokai_batch.job_execution_parameters_builder.global')
                ->setClass(StaticJobExecutionParametersBuilder::class)
                ->setArgument('$parameters', $config['global'])
                ->addTag('yokai_batch.job_execution_parameters_builder');
        }
        if ($config['per_job'] !== []) {
            $container->register('yokai_batch.job_execution_parameters_builder.per_job')
                ->setClass(PerJobJobExecutionParametersBuilder::class)
                ->setArgument('$perJobParameters', $config['per_job'])
                ->addTag('yokai_batch.job_execution_parameters_builder');
        }
    }

    /**
     * @param UserInterfaceConfig $config
     */
    private function configureUserInterface(ContainerBuilder $container, LoaderInterface $loader, array $config): void
    {
        if (!$config['enabled']) {
            return;
        }

        $loader->load('ui.xml');

        if (\class_exists(AbstractType::class)) {
            $container->register('yokai_batch.ui.filter_form', JobFilterType::class)
                ->addTag('form.type');
        }
        if (\interface_exists(TemplateRegistryInterface::class)) {
            $container->register('yokai_batch.ui.sonata_templating', SonataAdminTemplating::class)
                ->addArgument(new Reference('sonata.admin.global_template_registry'));
        }

        $attributes = $config['security']['attributes'];
        $container->setParameter('yokai_batch.ui.security_list_attribute', $attributes['list']);
        $container->setParameter('yokai_batch.ui.security_view_attribute', $attributes['view']);
        $container->setParameter('yokai_batch.ui.security_traces_attribute', $attributes['traces']);
        $container->setParameter('yokai_batch.ui.security_logs_attribute', $attributes['logs']);

        $templating = $config['templating'];
        if ($templating['service'] !== null) {
            $container->setAlias(TemplatingInterface::class, $templating['service']);
        } elseif ($templating['prefix'] !== null) {
            $container->register('yokai_batch.ui.templating', ConfigurableTemplating::class)
                ->addArgument($templating['prefix'])
                ->addArgument(['base_template' => $templating['base_template']]);
            $container->setAlias(TemplatingInterface::class, 'yokai_batch.ui.templating');
        }
    }
}
