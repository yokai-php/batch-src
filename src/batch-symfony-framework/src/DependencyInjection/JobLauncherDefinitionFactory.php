<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Messenger\MessageBusInterface;
use Yokai\Batch\Bridge\Symfony\Console\CommandRunner;
use Yokai\Batch\Bridge\Symfony\Console\RunCommandJobLauncher;
use Yokai\Batch\Bridge\Symfony\Messenger\DispatchMessageJobLauncher;
use Yokai\Batch\Bridge\Symfony\Messenger\MessengerJobsConfiguration;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Launcher\RoutingJobLauncher;
use Yokai\Batch\Launcher\SimpleJobLauncher;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * This is a helper for building services definitions of {@see JobLauncherInterface}.
 */
final class JobLauncherDefinitionFactory
{
    /**
     * Build a service definition from DSN string.
     */
    public static function fromDsn(string $dsn): Definition|Reference
    {
        $dsnParts = \parse_url($dsn);
        $launcherType = $dsnParts['scheme'] ?? null;
        \parse_str($dsnParts['query'] ?? '', $launcherConfig);
        /** @var array<string, string> $launcherConfig */

        return match ($launcherType) {
            'simple' => self::simple(),
            'console' => self::console($launcherConfig),
            'messenger' => self::messenger(),
            'service' => self::service($launcherConfig),
            default => throw new LogicException('Unsupported job launcher type "' . $launcherType . '".'),
        };
    }

    /**
     * Create the {@see RoutingJobLauncher} service definition when has configuration for it.
     *
     * @param array<string, string> $launchers
     * @param array<string, string> $routing
     */
    public static function routing(
        ContainerBuilder $container,
        array $launchers,
        string $default,
        array $routing,
    ): Definition {
        return new Definition(RoutingJobLauncher::class, [
            '$launchers' => ServiceLocatorTagPass::register($container, $launchers),
            '$default' => new Reference($default),
            '$routing' => $routing,
        ]);
    }

    private static function simple(): Definition
    {
        return new Definition(SimpleJobLauncher::class, [
            '$jobExecutionAccessor' => new Reference('yokai_batch.job_execution_accessor'),
            '$jobExecutor' => new Reference('yokai_batch.job_executor'),
        ]);
    }

    /**
     * @param array<string, string> $config
     */
    private static function console(array $config): Definition
    {
        $log = $config['log'] ?? 'batch_execute.log';

        return new Definition(RunCommandJobLauncher::class, [
            '$jobExecutionFactory' => new Reference('yokai_batch.job_execution_factory'),
            '$commandRunner' => new Definition(CommandRunner::class, [
                '$binDir' => '%kernel.project_dir%/bin',
                '$logDir' => '%kernel.logs_dir%',
            ]),
            '$jobExecutionStorage' => new Reference(JobExecutionStorageInterface::class),
            '$logFilename' => $log,
        ]);
    }

    private static function messenger(): Definition
    {
        return new Definition(DispatchMessageJobLauncher::class, [
            '$jobExecutionFactory' => new Reference('yokai_batch.job_execution_factory'),
            '$jobExecutionStorage' => new Reference(JobExecutionStorageInterface::class),
            '$messageBus' => new Reference(MessageBusInterface::class),
            '$messengerJobsConfiguration' => new Definition(MessengerJobsConfiguration::class, [
                '$routing' => '%yokai_batch.launcher.messenger_routing%',
            ]),
        ]);
    }

    /**
     * @param array<string, string> $config
     */
    private static function service(array $config): Reference
    {
        $service = $config['service'] ?? throw new LogicException(
            'Missing "service" parameter to configure the job launcher.',
        );

        return new Reference($service);
    }
}
