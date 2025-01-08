<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\DependencyInjection;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Yokai\Batch\Bridge\Symfony\Console\RunCommandJobLauncher;
use Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\YokaiBatchExtension;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\ConfigurableTemplating;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\SonataAdminTemplating;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\TemplatingInterface;
use Yokai\Batch\Bridge\Symfony\Messenger\DispatchMessageJobLauncher;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\ChainJobExecutionParametersBuilder;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\PerJobJobExecutionParametersBuilder;
use Yokai\Batch\Factory\JobExecutionParametersBuilder\StaticJobExecutionParametersBuilder;
use Yokai\Batch\Factory\JobExecutionParametersBuilderInterface;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Launcher\SimpleJobLauncher;
use Yokai\Batch\Storage\JobExecutionStorageInterface;
use Yokai\Batch\Storage\NullJobExecutionStorage;
use Yokai\Batch\Test\Launcher\BufferingJobLauncher;

class YokaiBatchExtensionTest extends TestCase
{
    /**
     * @dataProvider configs
     */
    public function test(
        array $config,
        ?callable $configure,
        string $storage,
        ?string $launcher,
        ?callable $templating,
        ?array $security
    ): void {
        $container = new ContainerBuilder();
        if ($configure !== null) {
            $configure($container);
        }

        (new YokaiBatchExtension())->load([$config], $container);

        $launcherActualService = (string)$container->getAlias(JobLauncherInterface::class);
        self::assertSame(
            $launcher ?? SimpleJobLauncher::class,
            $container->getDefinition($launcherActualService)->getClass()
        );
        self::assertSame(
            $storage,
            (string)$container->getAlias(JobExecutionStorageInterface::class)
        );
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
                (string)$container->getParameter('yokai_batch.ui.security_list_attribute')
            );
            self::assertSame(
                $security['view'],
                (string)$container->getParameter('yokai_batch.ui.security_view_attribute')
            );
            self::assertSame(
                $security['traces'],
                (string)$container->getParameter('yokai_batch.ui.security_traces_attribute')
            );
            self::assertSame(
                $security['logs'],
                (string)$container->getParameter('yokai_batch.ui.security_logs_attribute')
            );
        }
    }

    public function configs(): \Generator
    {
        yield [
            [],
            null,
            'yokai_batch.storage.filesystem',
            null,
            null,
            null,
        ];
        yield [
            ['storage' => ['filesystem' => null]],
            null,
            'yokai_batch.storage.filesystem',
            null,
            null,
            null,
        ];
        yield [
            ['storage' => ['dbal' => null]],
            null,
            'yokai_batch.storage.dbal',
            null,
            null,
            null,
        ];
        yield [
            ['storage' => ['service' => 'app.yokai_batch.storage']],
            fn(ContainerBuilder $container) => $container->register(
                'app.yokai_batch.storage',
                NullJobExecutionStorage::class,
            ),
            'app.yokai_batch.storage',
            null,
            null,
            null,
        ];
        yield [
            ['ui' => ['enabled' => true]],
            null,
            'yokai_batch.storage.filesystem',
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
        yield [
            ['ui' => ['enabled' => true, 'templating' => 'bootstrap4']],
            null,
            'yokai_batch.storage.filesystem',
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
        yield [
            [
                'ui' => [
                    'enabled' => true,
                    'templating' => ['prefix' => 'yokai-batch/tailwind', 'base_template' => 'layout.html.twig'],
                ],
            ],
            null,
            'yokai_batch.storage.filesystem',
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
        yield [
            ['ui' => ['enabled' => true, 'templating' => ['service' => 'app.yokai_batch_templating']]],
            fn(ContainerBuilder $container) => $container->register(
                'app.yokai_batch_templating',
                ConfigurableTemplating::class,
            ),
            'yokai_batch.storage.filesystem',
            null,
            function (Definition $templating, string $id) {
                self::assertSame($id, 'app.yokai_batch_templating');
            },
            [
                'list' => 'IS_AUTHENTICATED',
                'view' => 'IS_AUTHENTICATED',
                'traces' => 'IS_AUTHENTICATED',
                'logs' => 'IS_AUTHENTICATED',
            ],
        ];
        yield [
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
            'yokai_batch.storage.filesystem',
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
        yield [
            [
                'launcher' => [
                    'default' => 'simple',
                    'launchers' => [
                        'simple' => 'simple://simple',
                    ],
                ],
            ],
            null,
            'yokai_batch.storage.filesystem',
            SimpleJobLauncher::class,
            null,
            null,
        ];
        yield [
            [
                'launcher' => [
                    'default' => 'console',
                    'launchers' => [
                        'console' => 'console://console',
                    ],
                ],
            ],
            null,
            'yokai_batch.storage.filesystem',
            RunCommandJobLauncher::class,
            null,
            null,
        ];
        yield [
            [
                'launcher' => [
                    'default' => 'messenger',
                    'launchers' => [
                        'messenger' => 'messenger://messenger',
                    ],
                ],
            ],
            null,
            'yokai_batch.storage.filesystem',
            DispatchMessageJobLauncher::class,
            null,
            null,
        ];
        yield [
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
            'yokai_batch.storage.filesystem',
            BufferingJobLauncher::class,
            null,
            null,
        ];
    }

    /**
     * @dataProvider errors
     */
    public function testErrors(array $config, ?callable $configure, Exception $error): void
    {
        $this->expectExceptionObject($error);

        $container = new ContainerBuilder();
        if ($configure !== null) {
            $configure($container);
        }

        (new YokaiBatchExtension())->load([$config], $container);
    }

    public function errors(): \Generator
    {
        yield 'Storage : Unknown service' => [
            ['storage' => ['service' => 'unknown.service']],
            null,
            new LogicException('Configured default job execution storage service "unknown.service" does not exists.'),
        ];
        yield 'Storage : Service with no class' => [
            ['storage' => ['service' => 'service.with.no.class']],
            fn(ContainerBuilder $container) => $container->register('service.with.no.class'),
            new LogicException('Job execution storage service "service.with.no.class", has no class.'),
        ];
        yield 'Storage : Service without required interface' => [
            ['storage' => ['service' => 'service.without.required.interface']],
            fn(ContainerBuilder $container) => $container->register('service.without.required.interface', __CLASS__),
            new LogicException(
                'Job execution storage service "service.without.required.interface",' .
                ' is of class' .
                ' "Yokai\Batch\Tests\Bridge\Symfony\Framework\DependencyInjection\YokaiBatchExtensionTest",' .
                ' and must implements interface "Yokai\Batch\Storage\JobExecutionStorageInterface".'
            ),
        ];
        yield 'Templating : Not configured' => [
            ['ui' => ['enabled' => true, 'templating' => []]],
            null,
            new \InvalidArgumentException('You must either configure "service" or "prefix".'),
        ];
        yield 'Templating : Both configured' => [
            ['ui' => ['enabled' => true, 'templating' => ['service' => 'service.id', 'prefix' => 'prefix/']]],
            null,
            new \InvalidArgumentException('You cannot configure "service" and "prefix" at the same time.'),
        ];
        yield 'Templating : Unknown service' => [
            ['ui' => ['enabled' => true, 'templating' => ['service' => 'unknown.service']]],
            null,
            new LogicException('Configured UI templating service "unknown.service" does not exists.'),
        ];
        yield 'Templating : Service with no class' => [
            ['ui' => ['enabled' => true, 'templating' => ['service' => 'service.with.no.class']]],
            fn(ContainerBuilder $container) => $container->register('service.with.no.class'),
            new LogicException(
                'Configured UI templating service "service.with.no.class" ' .
                'must implements interface' .
                ' "Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\TemplatingInterface".',
            ),
        ];
        yield 'Templating : Service without required interface' => [
            ['ui' => ['enabled' => true, 'templating' => ['service' => 'service.without.required.interface']]],
            fn(ContainerBuilder $container) => $container->register('service.without.required.interface', __CLASS__),
            new LogicException(
                'Configured UI templating service "service.without.required.interface" ' .
                'must implements interface' .
                ' "Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\TemplatingInterface".',
            ),
        ];
        yield 'Job Launcher : Empty DSN' => [
            ['launcher' => ['default' => 'invalid', 'launchers' => ['invalid' => '']]],
            null,
            new InvalidConfigurationException(
                'Invalid configuration for path "yokai_batch.launcher.launchers.invalid": Invalid job launcher DSN.'
            ),
        ];
        yield 'Job Launcher : Invalid DSN' => [
            ['launcher' => ['default' => 'invalid', 'launchers' => ['invalid' => 'not a DSN']]],
            null,
            new InvalidConfigurationException(
                'Invalid configuration for path "yokai_batch.launcher.launchers.invalid": Invalid job launcher DSN.'
            ),
        ];
        yield 'Job Launcher : Unregistered launcher' => [
            ['launcher' => ['default' => 'unknown', 'launchers' => ['simple' => 'simple://simple']]],
            null,
            new LogicException(
                'Default job launcher "unknown" was not registered in launchers config. Available launchers are ["simple"].'
            ),
        ];
        yield 'Job Launcher : Unsupported launcher type' => [
            ['launcher' => ['default' => 'invalid', 'launchers' => ['invalid' => 'unknown://unknown']]],
            null,
            new LogicException('Unsupported job launcher type "unknown".'),
        ];
        yield 'Job Launcher : Unknown service' => [
            ['launcher' => ['default' => 'service', 'launchers' => ['service' => 'service://service?service=app.unknown']]],
            null,
            new ServiceNotFoundException('app.unknown'),
        ];
    }

    /**
     * @dataProvider parameters
     */
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
        self::assertTrue($defaultServiceBuilders instanceof TaggedIteratorArgument);
        /** @var TaggedIteratorArgument $defaultServiceBuilders */
        self::assertSame('yokai_batch.job_execution_parameters_builder', $defaultServiceBuilders->getTag());
    }

    public function parameters(): \Generator
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

    /**
     * @dataProvider invalidParameters
     */
    public function testInvalidParameters(array $config, \Exception $error): void
    {
        $this->expectExceptionObject($error);
        $this->createContainer($config);
    }

    public function invalidParameters(): \Generator
    {
        yield 'Per job parameters value must be an array' => [
            ['parameters' => ['per_job' => ['job.foo' => 'string']]],
            new InvalidConfigurationException(
                'Invalid configuration for path "yokai_batch.parameters.per_job.job.foo": Should be an array<string, mixed>.'
            ),
        ];
        yield 'Per job parameters value must be a string indexed array' => [
            ['parameters' => ['per_job' => ['job.foo' => [1, 2, 3]]]],
            new InvalidConfigurationException(
                'Invalid configuration for path "yokai_batch.parameters.per_job.job.foo": Should be an array<string, mixed>.'
            ),
        ];
    }

    private function createContainer(array $config, \Closure|null $configure = null): ContainerBuilder
    {
        $container = new ContainerBuilder();
        if ($configure !== null) {
            $configure($container);
        }
        $container->registerExtension(new YokaiBatchExtension());
        $container->loadFromExtension('yokai_batch', $config);

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->getCompilerPassConfig()->setAfterRemovingPasses([]);
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
