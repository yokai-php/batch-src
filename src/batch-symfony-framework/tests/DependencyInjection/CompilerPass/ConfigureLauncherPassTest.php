<?php

declare(strict_types=1);

namespace DependencyInjection\CompilerPass;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\CompilerPass\ConfigureLauncherPass;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Launcher\SimpleJobLauncher;

final class ConfigureLauncherPassTest extends TestCase
{
    public function testNominal(): void
    {
        $this->process(function (ContainerBuilder $container) {
            $container->register('yokai_batch.launcher.simple', SimpleJobLauncher::class);
            $container->setAlias(JobLauncherInterface::class, 'yokai_batch.launcher.simple');
        });

        self::assertTrue(true, 'No exception was raised');
    }

    public function testMissingService(): void
    {
        $this->expectExceptionObject(
            new LogicException('Job launcher service "app.yokai_batch_job_launcher" does not exists.')
        );
        $this->process(function (ContainerBuilder $container) {
            $container->setAlias(JobLauncherInterface::class, 'app.yokai_batch_job_launcher');
        });
    }

    public function testInvalidService(): void
    {
        $this->expectExceptionObject(
            new LogicException(
                'Job launcher service "app.yokai_batch_job_launcher" must implements interface "' . JobLauncherInterface::class . '".',
            ),
        );
        $this->process(function (ContainerBuilder $container) {
            $container->register('app.yokai_batch_job_launcher', self::class);
            $container->setAlias(JobLauncherInterface::class, 'app.yokai_batch_job_launcher');
        });
    }

    private function process(\Closure $configure): void
    {
        $container = new ContainerBuilder();
        $configure($container);
        (new ConfigureLauncherPass())->process($container);
    }
}
