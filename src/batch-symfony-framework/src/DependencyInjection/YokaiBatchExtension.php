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
use Yokai\Batch\Bridge\Monolog\StreamJobExecutionLoggerFactory;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Form\JobFilterType;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\PaginationConfiguration;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\ConfigurableTemplating;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\SonataAdminTemplating;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\TemplatingInterface;
use Yokai\Batch\Factory\JobExecutionIdGeneratorInterface;
use Yokai\Batch\Factory\JobExecutionLoggerFactory\InMemoryJobExecutionLoggerFactory;
use Yokai\Batch\Factory\JobExecutionLoggerFactory\NullJobExecutionLoggerFactory;
use Yokai\Batch\Factory\JobExecutionLoggerFactoryInterface;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\PerJobJobExecutionParametersBuilder;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\StaticJobExecutionParametersBuilder;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Logger\YokaiBatchLogger;
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
 * @phpstan-import-type LoggingConfig from Configuration
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
        $bridges = [
            'core.php' => true,
            'doctrine-orm.php' => $this->installed('doctrine-orm'),
            'logger.php' => true,
            'symfony-console.php' => $this->installed('symfony-console'),
            'symfony-messenger.php' => $this->installed('symfony-messenger'),
            'symfony-serializer.php' => $this->installed('symfony-serializer'),
            'symfony-validator.php' => $this->installed('symfony-validator'),
        ];
        foreach (\array_keys(\array_filter($bridges)) as $resource) {
            $loader->load($resource);
        }

        $this->configureStorage($container, $config['storage']);
        $this->configureLauncher($container, $config['launcher']);
        $this->configureParameters($container, $config['parameters']);
        $this->configureUserInterface($container, $loader, $config['ui']);
        $this->configureLogging($container, $config['logging']);

        $jobExecutionIdGeneratorDefinition = JobExecutionIdGeneratorDefinitionFactory::fromType($config['id']);
        $container->setDefinition(JobExecutionIdGeneratorInterface::class, $jobExecutionIdGeneratorDefinition);

        $container->registerAliasForArgument(YokaiBatchLogger::class, LoggerInterface::class, 'yokaiBatchLogger');
    }

    private function installed(string $package): bool
    {
        return InstalledVersions::isInstalled('yokai/batch-src')
            || InstalledVersions::isInstalled('yokai/batch-' . $package);
    }

    private function getLoader(ContainerBuilder $container): LoaderInterface
    {
        $locator = new FileLocator(__DIR__ . '/../Resources/services');
        $resolver = new ConfigLoader\LoaderResolver([
            new DependencyInjectionLoader\PhpFileLoader($container, $locator),
        ]);

        return new ConfigLoader\DelegatingLoader($resolver);
    }

    /**
     * @param StorageConfig $config
     */
    private function configureStorage(ContainerBuilder $container, array $config): void
    {
        if (isset($config['dsn'])) {
            $definitionOrReference = StorageDefinitionFactory::fromDsn($config['dsn']);
            if ($definitionOrReference instanceof Definition) {
                $container->setDefinition($defaultStorage = 'yokai_batch.storage', $definitionOrReference);
            } else {
                $defaultStorage = (string)$definitionOrReference;
            }
        } elseif (isset($config['service'])) {
            $defaultStorage = $config['service'];
        } elseif (isset($config['dbal'])) {
            $container
                ->register($defaultStorage = DoctrineDBALJobExecutionStorage::class)
                ->setArguments(
                    [
                        new Reference('doctrine'),
                        new Reference(JobExecutionLoggerFactoryInterface::class),
                        [
                            'connection' => $config['dbal']['connection'],
                            'table' => $config['dbal']['table'],
                        ],
                    ],
                )
            ;
        } else {
            $container
                ->register($defaultStorage = FilesystemJobExecutionStorage::class)
                ->setArguments([new Reference($config['filesystem']['serializer']), $config['filesystem']['dir']])
            ;
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

        $container->setParameter('yokai_batch.launcher.messenger_routing', $config['messenger']['routing'] ?? []);

        $idPerLauncherName = [];
        foreach ($config['launchers'] as $name => $dsn) {
            $definitionOrReference = JobLauncherDefinitionFactory::fromDsn($dsn);
            if ($definitionOrReference instanceof Definition) {
                $launcherId = 'yokai_batch.job_launcher.' . $name;
                $container->setDefinition($launcherId, $definitionOrReference);
            } else {
                $launcherId = (string)$definitionOrReference;
            }

            $idPerLauncherName[$name] = $launcherId;
            $parameterName = $name . 'JobLauncher';
            $container->registerAliasForArgument($launcherId, JobLauncherInterface::class, $parameterName);
        }

        $default = $idPerLauncherName[$config['default']];

        $routing = $config['routing'] ?? [];
        if ($routing !== []) {
            $container->setDefinition(
                $launcherId = 'yokai_batch.job_launcher.routing',
                JobLauncherDefinitionFactory::routing($container, $idPerLauncherName, $default, $routing),
            );
            $default = $launcherId;
        }

        $container->setAlias(JobLauncherInterface::class, $default);
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

        $loader->load('ui.php');

        if (\class_exists(AbstractType::class)) {
            $container->register(JobFilterType::class)
                ->addTag('form.type');
        }
        if (\interface_exists(TemplateRegistryInterface::class)) {
            $container->register(SonataAdminTemplating::class)
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

        $pagination = $config['pagination'];
        $container->register(PaginationConfiguration::class)
            ->addArgument($pagination['page_size'])
            ->addArgument($pagination['page_range']);
    }

    /**
     * @param LoggingConfig $config
     */
    private function configureLogging(ContainerBuilder $container, array $config): void
    {
        if ($config['type'] === 'service') {
            if ($config['service'] === null) {
                throw new LogicException(
                    'Cannot configure service logging: provide a service to use.',
                );
            }

            $container->setAlias(JobExecutionLoggerFactoryInterface::class, $config['service']);

            return;
        }

        if ($config['type'] === 'stream') {
            if (!$this->installed('monolog')) {
                throw new LogicException(
                    'Cannot configure stream logging: install "yokai/batch-monolog" first.',
                );
            }

            $container->register(JobExecutionLoggerFactoryInterface::class, StreamJobExecutionLoggerFactory::class)
                ->setArguments([
                    $config['stream']['directory'],
                    \array_map(fn(string $id) => new Reference($id), $config['stream']['processors']),
                    $config['stream']['formatter'] !== null ? new Reference($config['stream']['formatter']) : null,
                    $config['stream']['sub_directories'],
                    $config['stream']['chars_per_directory'],
                ]);

            return;
        }

        if ($config['type'] === 'null') {
            $container->register(JobExecutionLoggerFactoryInterface::class, NullJobExecutionLoggerFactory::class);

            return;
        }

        $container->register(JobExecutionLoggerFactoryInterface::class, InMemoryJobExecutionLoggerFactory::class);
    }
}
