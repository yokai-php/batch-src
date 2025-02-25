<?php

declare(strict_types=1);

namespace DependencyInjection\CompilerPass;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\CompilerPass\ConfigureTemplatingPass;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\ConfigurableTemplating;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\TemplatingInterface;

final class ConfigureTemplatingPassTest extends TestCase
{
    public function testNominal(): void
    {
        $this->process(function (ContainerBuilder $container) {
            $container->register('yokai_batch.ui.templating', ConfigurableTemplating::class);
            $container->setAlias(TemplatingInterface::class, 'yokai_batch.ui.templating');
        });

        self::assertTrue(true, 'No exception was raised');
    }

    public function testMissingAlias(): void
    {
        $this->process(function (ContainerBuilder $container) {
        });

        self::assertTrue(true, 'No exception was raised');
    }

    public function testMissingService(): void
    {
        $this->expectExceptionObject(
            new LogicException('UI templating service "app.yokai_batch_templating" does not exists.'),
        );
        $this->process(function (ContainerBuilder $container) {
            $container->setAlias(TemplatingInterface::class, 'app.yokai_batch_templating');
        });
    }

    public function testInvalidService(): void
    {
        $this->expectExceptionObject(
            new LogicException(
                'UI templating service "app.yokai_batch_templating" must implements interface "' . TemplatingInterface::class . '".',
            ),
        );
        $this->process(function (ContainerBuilder $container) {
            $container->register('app.yokai_batch_templating', self::class);
            $container->setAlias(TemplatingInterface::class, 'app.yokai_batch_templating');
        });
    }

    private function process(\Closure $configure): void
    {
        $container = new ContainerBuilder();
        $configure($container);
        (new ConfigureTemplatingPass())->process($container);
    }
}
