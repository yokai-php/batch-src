<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\DependencyInjection;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALJobExecutionStorage;
use Yokai\Batch\Bridge\Monolog\StreamJobExecutionLoggerFactory;
use Yokai\Batch\Bridge\Symfony\Console\RunCommandJobLauncher;
use Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\YokaiBatchExtension;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\ConfigurableTemplating;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\SonataAdminTemplating;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\TemplatingInterface;
use Yokai\Batch\Bridge\Symfony\Framework\YokaiBatchBundle;
use Yokai\Batch\Bridge\Symfony\Messenger\DispatchMessageJobLauncher;
use Yokai\Batch\Bridge\Symfony\Uid\Factory\RandomBasedUuidJobExecutionIdGenerator;
use Yokai\Batch\Bridge\Symfony\Uid\Factory\TimeBasedUuidJobExecutionIdGenerator;
use Yokai\Batch\Bridge\Symfony\Uid\Factory\UlidJobExecutionIdGenerator;
use Yokai\Batch\Factory\JobExecutionIdGeneratorInterface;
use Yokai\Batch\Factory\JobExecutionLoggerFactory\InMemoryJobExecutionLoggerFactory;
use Yokai\Batch\Factory\JobExecutionLoggerFactory\NullJobExecutionLoggerFactory;
use Yokai\Batch\Factory\JobExecutionLoggerFactoryInterface;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\ChainJobExecutionParametersBuilder;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\PerJobJobExecutionParametersBuilder;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\StaticJobExecutionParametersBuilder;
use Yokai\Batch\Factory\JobExecutionParametersBuilderInterface;
use Yokai\Batch\Factory\UniqidJobExecutionIdGenerator;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Launcher\RoutingJobLauncher;
use Yokai\Batch\Launcher\SimpleJobLauncher;
use Yokai\Batch\Serializer\JsonJobExecutionSerializer;
use Yokai\Batch\Storage\FilesystemJobExecutionStorage;
use Yokai\Batch\Storage\JobExecutionStorageInterface;
use Yokai\Batch\Storage\NullJobExecutionStorage;
use Yokai\Batch\Test\Launcher\BufferingJobLauncher;

final class YokaiBatchExtensionTest extends TestCase
{
    #[DataProvider('storage')]
    public function testStorage(
        array $config,
        \Closure|null $configure,
        string $storage,
        \Closure|null $assert = null,
    ): void {
        $container = $this->createContainer($config, $configure);

        $jobExecutionStorageService = $this->getDefinition($container, JobExecutionStorageInterface::class);
        self::assertNotNull($jobExecutionStorageService);
        self::assertSame($storage, $jobExecutionStorageService->getClass());

        if ($assert !== null) {
            $assert($container->findDefinition($storage));
        }
    }

    public static function storage(): \Generator
    {
        yield 'Default config' => [
            [],
            null,
            FilesystemJobExecutionStorage::class,
        ];
        yield 'Filesystem with default values' => [
            ['storage' => ['filesystem' => null]],
            null,
            FilesystemJobExecutionStorage::class,
        ];
        yield 'DBAL with default values' => [
            ['storage' => ['dbal' => null]],
            null,
            DoctrineDBALJobExecutionStorage::class,
            function (Definition $definition) {
                [$doctrine, $loggerFactory, $options] = $definition->getArguments();
                self::assertInstanceOf(Reference::class, $doctrine);
                self::assertSame('doctrine', (string)$doctrine);
                self::assertInstanceOf(Reference::class, $loggerFactory);
                self::assertSame(JobExecutionLoggerFactoryInterface::class, (string)$loggerFactory);
                self::assertIsArray($options);
            },
        ];
        yield 'Custom service' => [
            ['storage' => ['service' => NullJobExecutionStorage::class]],
            fn(ContainerBuilder $container) => $container->register(NullJobExecutionStorage::class),
            NullJobExecutionStorage::class,
        ];
        yield 'DSN filesystem' => [
            ['storage' => 'filesystem://%kernel.project_dir%/var/batch'],
            fn(ContainerBuilder $container) => $container->setParameter('kernel.project_dir', __DIR__),
            FilesystemJobExecutionStorage::class,
        ];
        yield 'DSN filesystem absolute path' => [
            ['storage' => 'filesystem:///tmp/batch'],
            null,
            FilesystemJobExecutionStorage::class,
        ];
        yield 'DSN filesystem with serializer' => [
            ['storage' => 'filesystem://tmp/batch?serializer=' . JsonJobExecutionSerializer::class],
            null,
            FilesystemJobExecutionStorage::class,
        ];
        yield 'DSN dbal' => [
            ['storage' => 'dbal://default'],
            null,
            DoctrineDBALJobExecutionStorage::class,
        ];
        yield 'DSN service' => [
            ['storage' => 'service://service?id=' . NullJobExecutionStorage::class],
            fn(ContainerBuilder $container) => $container->register(NullJobExecutionStorage::class),
            NullJobExecutionStorage::class,
        ];
    }

    #[DataProvider('launcher')]
    public function testLauncher(
        array $config,
        \Closure|null $configure,
        array $launchers,
        string $default,
        \Closure|null $assert = null,
    ): void {
        $container = $this->createContainer($config, $configure);

        self::assertSame($default, (string)$container->getAlias(JobLauncherInterface::class));
        foreach ($launchers as $id => $class) {
            self::assertSame($class, $container->getDefinition($id)->getClass());
        }

        if ($assert !== null) {
            $assert($container);
        }
    }

    public static function launcher(): \Generator
    {
        yield 'Default config' => [
            [],
            null,
            ['yokai_batch.job_launcher.simple' => SimpleJobLauncher::class],
            'yokai_batch.job_launcher.simple',
        ];
        yield 'Multiple launchers' => [
            [
                'launcher' => [
                    'default' => 'messenger',
                    'launchers' => [
                        'messenger' => 'messenger://messenger',
                        'console' => 'console://console',
                    ],
                ],
            ],
            null,
            [
                'yokai_batch.job_launcher.messenger' => DispatchMessageJobLauncher::class,
                'yokai_batch.job_launcher.console' => RunCommandJobLauncher::class,
            ],
            'yokai_batch.job_launcher.messenger',
        ];
        yield 'Messenger launcher routing' => [
            [
                'launcher' => [
                    'default' => 'messenger',
                    'launchers' => [
                        'messenger' => 'messenger://messenger',
                    ],
                    'messenger' => [
                        'routing' => [
                            'job1' => 'async',
                            'job2' => 'sync',
                        ],
                    ],
                ],
            ],
            null,
            [
                'yokai_batch.job_launcher.messenger' => DispatchMessageJobLauncher::class,
            ],
            'yokai_batch.job_launcher.messenger',
            function (ContainerBuilder $container) {
                $messengerJobsConfiguration = $container->getDefinition('yokai_batch.job_launcher.messenger')
                    ->getArgument('$messengerJobsConfiguration');
                self::assertInstanceOf(Definition::class, $messengerJobsConfiguration);
                self::assertSame(
                    '%yokai_batch.launcher.messenger_routing%',
                    $messengerJobsConfiguration->getArgument('$routing'),
                );
                self::assertSame(
                    [
                        'job1' => 'async',
                        'job2' => 'sync',
                    ],
                    $container->getParameter('yokai_batch.launcher.messenger_routing'),
                );
            },
        ];
        yield 'Service launcher' => [
            [
                'launcher' => [
                    'default' => 'service',
                    'launchers' => [
                        'service' => 'service://service?service=app.job_launcher',
                    ],
                ],
            ],
            fn(ContainerBuilder $container) => $container->register(
                'app.job_launcher',
                BufferingJobLauncher::class,
            ),
            ['app.job_launcher' => BufferingJobLauncher::class],
            'app.job_launcher',
        ];
        yield 'Routing launcher' => [
            [
                'launcher' => [
                    'default' => 'simple',
                    'launchers' => [
                        'simple' => 'simple://simple',
                        'messenger' => 'messenger://messenger',
                        'console' => 'console://console',
                    ],
                    'routing' => [
                        'job1' => 'messenger',
                        'job2' => 'console',
                    ],
                ],
            ],
            null,
            [
                'yokai_batch.job_launcher.simple' => SimpleJobLauncher::class,
                'yokai_batch.job_launcher.messenger' => DispatchMessageJobLauncher::class,
                'yokai_batch.job_launcher.console' => RunCommandJobLauncher::class,
                'yokai_batch.job_launcher.routing' => RoutingJobLauncher::class,
            ],
            'yokai_batch.job_launcher.routing',
            function (ContainerBuilder $container) {
                self::assertSame(
                    [
                        'job1' => 'messenger',
                        'job2' => 'console',
                    ],
                    $container->getDefinition('yokai_batch.job_launcher.routing')->getArgument('$routing'),
                );
            },
        ];
    }

    #[DataProvider('userInterface')]
    public function testUserInterface(
        array $config,
        \Closure|null $configure,
        \Closure|null $templating,
        array|null $security,
    ): void {
        $container = $this->createContainer($config, $configure);

        if ($templating === null && $security === null) {
            self::assertFalse($container->hasAlias(TemplatingInterface::class));
            self::assertFalse($container->hasParameter('yokai_batch.ui.security_list_attribute'));
            self::assertFalse($container->hasParameter('yokai_batch.ui.security_view_attribute'));
            self::assertFalse($container->hasParameter('yokai_batch.ui.security_traces_attribute'));
            self::assertFalse($container->hasParameter('yokai_batch.ui.security_logs_attribute'));
        } else {
            $templatingId = (string)$container->getAlias(TemplatingInterface::class);
            $templating($container->getDefinition($templatingId), $templatingId);

            self::assertSame(
                $security['list'],
                (string)$container->getParameter('yokai_batch.ui.security_list_attribute'),
            );
            self::assertSame(
                $security['view'],
                (string)$container->getParameter('yokai_batch.ui.security_view_attribute'),
            );
            self::assertSame(
                $security['traces'],
                (string)$container->getParameter('yokai_batch.ui.security_traces_attribute'),
            );
            self::assertSame(
                $security['logs'],
                (string)$container->getParameter('yokai_batch.ui.security_logs_attribute'),
            );
        }
    }

    public static function userInterface(): \Generator
    {
        yield 'Default config' => [
            [],
            null,
            null,
            null,
        ];
        yield 'Default when enabled' => [
            ['ui' => ['enabled' => true]],
            null,
            function (Definition $templating) {
                self::assertSame(ConfigurableTemplating::class, $templating->getClass());
                self::assertSame('@YokaiBatch/bootstrap4', $templating->getArgument(0));
                self::assertSame(['base_template' => 'base.html.twig'], $templating->getArgument(1));
            },
            [
                'list' => 'IS_AUTHENTICATED',
                'view' => 'IS_AUTHENTICATED',
                'traces' => 'IS_AUTHENTICATED',
                'logs' => 'IS_AUTHENTICATED',
            ],
        ];
        yield 'Bootstrap4 shortcut' => [
            ['ui' => ['enabled' => true, 'templating' => 'bootstrap4']],
            null,
            function (Definition $templating) {
                self::assertSame(ConfigurableTemplating::class, $templating->getClass());
                self::assertSame('@YokaiBatch/bootstrap4', $templating->getArgument(0));
                self::assertSame(['base_template' => 'base.html.twig'], $templating->getArgument(1));
            },
            [
                'list' => 'IS_AUTHENTICATED',
                'view' => 'IS_AUTHENTICATED',
                'traces' => 'IS_AUTHENTICATED',
                'logs' => 'IS_AUTHENTICATED',
            ],
        ];
        yield 'Custom prefix & base template' => [
            [
                'ui' => [
                    'enabled' => true,
                    'templating' => ['prefix' => 'yokai-batch/tailwind', 'base_template' => 'layout.html.twig'],
                ],
            ],
            null,
            function (Definition $templating) {
                self::assertSame(ConfigurableTemplating::class, $templating->getClass());
                self::assertSame('yokai-batch/tailwind', $templating->getArgument(0));
                self::assertSame(['base_template' => 'layout.html.twig'], $templating->getArgument(1));
            },
            [
                'list' => 'IS_AUTHENTICATED',
                'view' => 'IS_AUTHENTICATED',
                'traces' => 'IS_AUTHENTICATED',
                'logs' => 'IS_AUTHENTICATED',
            ],
        ];
        yield 'Custom service' => [
            ['ui' => ['enabled' => true, 'templating' => ['service' => 'app.yokai_batch_templating']]],
            fn(ContainerBuilder $container) => $container->register(
                'app.yokai_batch_templating',
                ConfigurableTemplating::class,
            ),
            function (Definition $templating, string $id) {
                self::assertSame('app.yokai_batch_templating', $id);
            },
            [
                'list' => 'IS_AUTHENTICATED',
                'view' => 'IS_AUTHENTICATED',
                'traces' => 'IS_AUTHENTICATED',
                'logs' => 'IS_AUTHENTICATED',
            ],
        ];
        yield 'Sonata with custom roles' => [
            [
                'ui' => [
                    'enabled' => true,
                    'templating' => 'sonata',
                    'security' => [
                        'attributes' => [
                            'list' => 'ROLE_ADMIN',
                            'view' => 'ROLE_ADMIN',
                            'traces' => 'ROLE_SUPERADMIN',
                            'logs' => 'ROLE_SUPERADMIN',
                        ],
                    ],
                ],
            ],
            null,
            function (Definition $templating) {
                self::assertSame(SonataAdminTemplating::class, $templating->getClass());
            },
            [
                'list' => 'ROLE_ADMIN',
                'view' => 'ROLE_ADMIN',
                'traces' => 'ROLE_SUPERADMIN',
                'logs' => 'ROLE_SUPERADMIN',
            ],
        ];
    }

    #[DataProvider('parameters')]
    public function testParameters(array $config, array|null $global, array|null $perJob): void
    {
        $container = $this->createContainer($config);

        $globalService = $this->getDefinition($container, 'yokai_batch.job_execution_parameters_builder.global');
        if ($global !== null) {
            self::assertNotNull($globalService);
            self::assertSame(StaticJobExecutionParametersBuilder::class, $globalService->getClass());
            self::assertTrue($globalService->hasTag('yokai_batch.job_execution_parameters_builder'));
            self::assertSame($global, $globalService->getArgument('$parameters'));
        } else {
            self::assertNull($globalService);
        }
        $perJobService = $this->getDefinition($container, 'yokai_batch.job_execution_parameters_builder.per_job');
        if ($perJob !== null) {
            self::assertNotNull($perJobService);
            self::assertSame(PerJobJobExecutionParametersBuilder::class, $perJobService->getClass());
            self::assertTrue($perJobService->hasTag('yokai_batch.job_execution_parameters_builder'));
            self::assertSame($perJob, $perJobService->getArgument('$perJobParameters'));
        } else {
            self::assertNull($perJobService);
        }
        $defaultService = $this->getDefinition($container, JobExecutionParametersBuilderInterface::class);
        self::assertNotNull($defaultService);
        self::assertSame(ChainJobExecutionParametersBuilder::class, $defaultService->getClass());
        $defaultServiceBuilders = $defaultService->getArgument(0);
        self::assertInstanceOf(TaggedIteratorArgument::class, $defaultServiceBuilders);
        /** @var TaggedIteratorArgument $defaultServiceBuilders */
        self::assertSame('yokai_batch.job_execution_parameters_builder', $defaultServiceBuilders->getTag());
    }

    public static function parameters(): \Generator
    {
        yield 'Global parameters' => [
            ['parameters' => ['global' => ['global' => true]]],
            ['global' => true],
            null,
        ];
        yield 'Per job parameters' => [
            ['parameters' => ['per_job' => ['job.foo' => ['foo' => true], 'job.bar' => ['bar' => true]]]],
            null,
            ['job.foo' => ['foo' => true], 'job.bar' => ['bar' => true]],
        ];
        yield 'Global AND per job parameters' => [
            ['parameters' => [
                'global' => ['global' => true],
                'per_job' => ['job.foo' => ['foo' => true], 'job.bar' => ['bar' => true]],
            ]],
            ['global' => true],
            ['job.foo' => ['foo' => true], 'job.bar' => ['bar' => true]],
        ];
    }

    #[DataProvider('errors')]
    public function testErrors(array $config, Exception $error): void
    {
        $this->expectExceptionObject($error);
        $this->createContainer($config);
    }

    public static function errors(): \Generator
    {
        yield 'Templating : Not configured' => [
            ['ui' => ['enabled' => true, 'templating' => []]],
            new \InvalidArgumentException('You must either configure "service" or "prefix".'),
        ];
        yield 'Templating : Both configured' => [
            ['ui' => ['enabled' => true, 'templating' => ['service' => 'service.id', 'prefix' => 'prefix/']]],
            new \InvalidArgumentException('You cannot configure "service" and "prefix" at the same time.'),
        ];
        yield 'Job Launcher : Empty DSN' => [
            ['launcher' => ['default' => 'invalid', 'launchers' => ['invalid' => '']]],
            new InvalidConfigurationException(
                'Invalid configuration for path "yokai_batch.launcher.launchers.invalid": Invalid job launcher DSN.',
            ),
        ];
        yield 'Job Launcher : Invalid DSN' => [
            ['launcher' => ['default' => 'invalid', 'launchers' => ['invalid' => 'not a DSN']]],
            new InvalidConfigurationException(
                'Invalid configuration for path "yokai_batch.launcher.launchers.invalid": Invalid job launcher DSN.',
            ),
        ];
        yield 'Job Launcher : Unregistered launcher' => [
            ['launcher' => ['default' => 'unknown', 'launchers' => ['simple' => 'simple://simple']]],
            new LogicException(
                'Default job launcher "unknown" was not registered in launchers config. Available launchers are ["simple"].',
            ),
        ];
        yield 'Job Launcher : Unsupported launcher type' => [
            ['launcher' => ['default' => 'invalid', 'launchers' => ['invalid' => 'unknown://unknown']]],
            new LogicException('Unsupported job launcher type "unknown".'),
        ];
        yield 'Per job parameters value must be an array' => [
            ['parameters' => ['per_job' => ['job.foo' => 'string']]],
            new InvalidConfigurationException(
                'Invalid configuration for path "yokai_batch.parameters.per_job.job.foo": Should be an array<string, mixed>.',
            ),
        ];
        yield 'Per job parameters value must be a string indexed array' => [
            ['parameters' => ['per_job' => ['job.foo' => [1, 2, 3]]]],
            new InvalidConfigurationException(
                'Invalid configuration for path "yokai_batch.parameters.per_job.job.foo": Should be an array<string, mixed>.',
            ),
        ];
    }

    #[DataProvider('id')]
    public function testId(array $config, string $idGenerator): void
    {
        $container = $this->createContainer($config);

        $idGeneratorDefinition = $this->getDefinition($container, JobExecutionIdGeneratorInterface::class);
        self::assertSame($idGenerator, $idGeneratorDefinition->getClass());
    }

    public static function id(): \Generator
    {
        yield 'Default config' => [
            [],
            UniqidJobExecutionIdGenerator::class,
        ];
        yield 'Explicit uniqid' => [
            ['id' => 'uniqid'],
            UniqidJobExecutionIdGenerator::class,
        ];
        yield 'Symfony random based UUID' => [
            ['id' => 'symfony.uuid.random'],
            RandomBasedUuidJobExecutionIdGenerator::class,
        ];
        yield 'Symfony time based UUID' => [
            ['id' => 'symfony.uuid.time'],
            TimeBasedUuidJobExecutionIdGenerator::class,
        ];
        yield 'Symfony ULID' => [
            ['id' => 'symfony.ulid'],
            UlidJobExecutionIdGenerator::class,
        ];
    }

    #[DataProvider('logging')]
    public function testLogging(
        array $config,
        \Closure|null $configure,
        string $expectedClass,
        \Closure|null $assert = null,
    ): void {
        $container = $this->createContainer($config, $configure);

        $definition = $this->getDefinition($container, JobExecutionLoggerFactoryInterface::class);
        self::assertNotNull($definition);
        self::assertSame($expectedClass, $definition->getClass());

        if ($assert !== null) {
            $assert($container->findDefinition(JobExecutionLoggerFactoryInterface::class));
        }
    }

    public static function logging(): \Generator
    {
        yield 'Default config' => [
            [],
            null,
            InMemoryJobExecutionLoggerFactory::class,
        ];
        yield 'Memory explicit' => [
            ['logging' => ['type' => 'memory']],
            null,
            InMemoryJobExecutionLoggerFactory::class,
        ];
        yield 'Null' => [
            ['logging' => ['type' => 'null']],
            null,
            NullJobExecutionLoggerFactory::class,
        ];
        yield 'Stream default directory' => [
            ['logging' => ['type' => 'stream']],
            null,
            StreamJobExecutionLoggerFactory::class,
            function (Definition $definition) {
                [$directory] = $definition->getArguments();
                self::assertSame('%kernel.logs_dir%/batch', $directory);
            },
        ];
        yield 'Stream custom directory' => [
            ['logging' => ['type' => 'stream', 'stream' => ['directory' => '/custom/logs']]],
            null,
            StreamJobExecutionLoggerFactory::class,
            function (Definition $definition) {
                [$directory] = $definition->getArguments();
                self::assertSame('/custom/logs', $directory);
            },
        ];
        yield 'Stream with subdirectories' => [
            ['logging' => ['type' => 'stream', 'stream' => ['directory' => '/tmp', 'sub_directories' => 2, 'chars_per_directory' => 3]]],
            null,
            StreamJobExecutionLoggerFactory::class,
            function (Definition $definition) {
                [, , , $subDirectories, $charsPerDirectory] = $definition->getArguments();
                self::assertSame(2, $subDirectories);
                self::assertSame(3, $charsPerDirectory);
            },
        ];
        yield 'Stream with processors' => [
            ['logging' => ['type' => 'stream', 'stream' => ['processors' => ['my.processor']]]],
            fn(ContainerBuilder $container) => $container->register('my.processor'),
            StreamJobExecutionLoggerFactory::class,
            function (Definition $definition) {
                [, $processors] = $definition->getArguments();
                self::assertCount(1, $processors);
                self::assertInstanceOf(Reference::class, $processors[0]);
                self::assertSame('my.processor', (string)$processors[0]);
            },
        ];
        yield 'Stream with formatter' => [
            ['logging' => ['type' => 'stream', 'stream' => ['formatter' => 'my.formatter']]],
            fn(ContainerBuilder $container) => $container->register('my.formatter'),
            StreamJobExecutionLoggerFactory::class,
            function (Definition $definition) {
                [, , $formatter] = $definition->getArguments();
                self::assertInstanceOf(Reference::class, $formatter);
                self::assertSame('my.formatter', (string)$formatter);
            },
        ];
        yield 'Custom service' => [
            ['logging' => ['type' => 'service', 'service' => NullJobExecutionLoggerFactory::class]],
            fn(ContainerBuilder $container) => $container->register(NullJobExecutionLoggerFactory::class),
            NullJobExecutionLoggerFactory::class,
        ];
    }

    private function createContainer(array $config, \Closure|null $configure = null): ContainerBuilder
    {
        $container = new ContainerBuilder();
        if ($configure !== null) {
            $configure($container);
        }
        $bundle = new YokaiBatchBundle();
        $extension = $bundle->getContainerExtension();
        \assert($extension instanceof YokaiBatchExtension);
        $container->registerExtension($extension);
        $container->loadFromExtension('yokai_batch', $config);

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->getCompilerPassConfig()->setAfterRemovingPasses([]);
        $bundle->build($container);
        $container->compile();

        return $container;
    }

    private function getDefinition(ContainerBuilder $container, string $id): Definition|null
    {
        try {
            return $container->findDefinition($id);
        } catch (ServiceNotFoundException) {
            return null;
        }
    }
}
