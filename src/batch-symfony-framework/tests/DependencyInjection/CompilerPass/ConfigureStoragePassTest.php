<?php

declare(strict_types=1);

namespace DependencyInjection\CompilerPass;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\CompilerPass\ConfigureStoragePass;
use Yokai\Batch\Storage\FilesystemJobExecutionStorage;
use Yokai\Batch\Storage\JobExecutionStorageInterface;
use Yokai\Batch\Storage\ListableJobExecutionStorageInterface;
use Yokai\Batch\Storage\QueryableJobExecutionStorageInterface;

final class ConfigureStoragePassTest extends TestCase
{
    public function testNominal(): void
    {
        $container = $this->process(function (ContainerBuilder $container) {
            $container->register('yokai_batch.storage.filesystem', FilesystemJobExecutionStorage::class);
            $container->setAlias(JobExecutionStorageInterface::class, 'yokai_batch.storage.filesystem');
        });

        self::assertTrue($container->hasAlias(ListableJobExecutionStorageInterface::class));
        self::assertTrue($container->hasAlias(QueryableJobExecutionStorageInterface::class));
    }

    public function testMissingService(): void
    {
        $this->expectExceptionObject(
            new LogicException('Job execution storage service "app.yokai_batch_storage" does not exists.'),
        );
        $this->process(function (ContainerBuilder $container) {
            $container->setAlias(JobExecutionStorageInterface::class, 'app.yokai_batch_storage');
        });
    }

    public function testInvalidService(): void
    {
        $this->expectExceptionObject(
            new LogicException(
                'Job execution storage service "app.yokai_batch_storage" must implements interface "' . JobExecutionStorageInterface::class . '".',
            ),
        );
        $this->process(function (ContainerBuilder $container) {
            $container->register('app.yokai_batch_storage', self::class);
            $container->setAlias(JobExecutionStorageInterface::class, 'app.yokai_batch_storage');
        });
    }

    private function process(\Closure $configure): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $configure($container);
        (new ConfigureStoragePass())->process($container);

        return $container;
    }
}
