<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
}
