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
use Yokai\Batch\Factory\JobExecutionFactory;
use Yokai\Batch\Job\JobExecutionAccessor;
use Yokai\Batch\Job\JobExecutor;
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
        $parsed = Dsn::parse($dsn);
        $type = $parsed->getScheme();

        return match ($type) {
            'simple' => self::simple(),
            'console' => self::console($parsed),
            'messenger' => self::messenger(),
            'service' => self::service($parsed),
            default => throw new LogicException('Unsupported job launcher type "' . $type . '".'),
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
            '$jobExecutionAccessor' => new Reference(JobExecutionAccessor::class),
            '$jobExecutor' => new Reference(JobExecutor::class),
        ]);
    }

    private static function console(Dsn $dsn): Definition
    {
        return new Definition(RunCommandJobLauncher::class, [
            '$jobExecutionFactory' => new Reference(JobExecutionFactory::class),
            '$commandRunner' => new Definition(CommandRunner::class, [
                '$binDir' => '%kernel.project_dir%/bin',
                '$logDir' => '%kernel.logs_dir%',
            ]),
            '$jobExecutionStorage' => new Reference(JobExecutionStorageInterface::class),
            '$logFilename' => $dsn->getOption('log', 'batch_execute.log'),
        ]);
    }

    private static function messenger(): Definition
    {
        return new Definition(DispatchMessageJobLauncher::class, [
            '$jobExecutionFactory' => new Reference(JobExecutionFactory::class),
            '$jobExecutionStorage' => new Reference(JobExecutionStorageInterface::class),
            '$messageBus' => new Reference(MessageBusInterface::class),
            '$messengerJobsConfiguration' => new Definition(MessengerJobsConfiguration::class, [
                '$routing' => '%yokai_batch.launcher.messenger_routing%',
            ]),
        ]);
    }

    private static function service(Dsn $dsn): Reference
    {
        $service = $dsn->getOption('service') ?? throw new LogicException(
            'Missing "service" parameter to configure the job launcher.',
        );

        return new Reference($service);
    }
}
