<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\CompilerPass\ConfigureLauncherPass;
use Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\CompilerPass\ConfigureStoragePass;
use Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\CompilerPass\ConfigureTemplatingPass;
use Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\CompilerPass\RegisterJobsCompilerPass;
use Yokai\Batch\Bridge\Symfony\Framework\YokaiBatchBundle;

final class YokaiBatchBundleTest extends TestCase
{
    public function testBuild(): void
    {
        $container = new ContainerBuilder();
        $passesBefore = $container->getCompilerPassConfig()->getPasses();

        (new YokaiBatchBundle())->build($container);

        $passes = [];
        foreach ($container->getCompilerPassConfig()->getBeforeOptimizationPasses() as $pass) {
            if (!\in_array($pass, $passesBefore, true)) {
                $passes[] = $pass::class;
            }
        }
        self::assertSame(
            [
                ConfigureLauncherPass::class,
                ConfigureStoragePass::class,
                ConfigureTemplatingPass::class,
                RegisterJobsCompilerPass::class,
            ],
            $passes,
        );
    }
}
