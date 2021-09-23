<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\DependencyInjection;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\YokaiBatchExtension;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Storage\JobExecutionStorageInterface;
use Yokai\Batch\Storage\NullJobExecutionStorage;

class YokaiBatchExtensionTest extends TestCase
{
    /**
     * @dataProvider configs
     */
    public function test(array $config, string $storage): void
    {
        $container = new ContainerBuilder();
        $container->register('app.yokai_batch.storage', NullJobExecutionStorage::class);

        (new YokaiBatchExtension())->load([$config], $container);

        self::assertSame(
            'yokai_batch.job_launcher.dispatch_message',
            (string)$container->getAlias(JobLauncherInterface::class)
        );
        self::assertSame(
            $storage,
            (string)$container->getAlias(JobExecutionStorageInterface::class)
        );
    }

    public function configs(): \Generator
    {
        yield [[], 'yokai_batch.storage.filesystem'];
        yield [['storage' => ['filesystem' => null]], 'yokai_batch.storage.filesystem'];
        yield [['storage' => ['dbal' => null]], 'yokai_batch.storage.dbal'];
        yield [['storage' => ['service' => 'app.yokai_batch.storage']], 'app.yokai_batch.storage'];
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
    }
}
