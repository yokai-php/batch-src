<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Yokai\Batch\Bridge\Symfony\Framework\YokaiBatchBundle;
use Yokai\Batch\Job\Item\ItemJob;
use Yokai\Batch\Job\Item\Processor\NullProcessor;
use Yokai\Batch\Job\Item\Reader\StaticIterableReader;
use Yokai\Batch\Registry\JobRegistry;
use Yokai\Batch\Storage\NullJobExecutionStorage;
use Yokai\Batch\Test\Job\Item\Writer\InMemoryWriter;

class YokaiBatchBundleTest extends TestCase
{
    public function testBuild(): void
    {
        $container = new ContainerBuilder();
        $container->register('yokai_batch.job_registry', JobRegistry::class)
            ->setPublic(true);

        $this->job($container, 'job.named.with.service.id')
            ->addTag('yokai_batch.job');
        $this->job($container, 'job.named.with.tag.attribute')
            ->addTag('yokai_batch.job', ['job' => 'job.name.in.attribute']);

        (new YokaiBatchBundle())->build($container);

        $container->compile();
        $registry = $container->get('yokai_batch.job_registry');
        self::assertInstanceOf(ItemJob::class, $registry->get('job.named.with.service.id'));
        self::assertInstanceOf(ItemJob::class, $registry->get('job.name.in.attribute'));
    }

    private function job(ContainerBuilder $container, string $id): Definition
    {
        return $container->register($id, ItemJob::class)
            ->setArgument('$batchSize', 100)
            ->setArgument('$reader', (new Definition(StaticIterableReader::class))->setArgument('$items', []))
            ->setArgument('$processor', new Definition(NullProcessor::class))
            ->setArgument('$writer', new Definition(InMemoryWriter::class))
            ->setArgument('$executionStorage', new Definition(NullJobExecutionStorage::class))
        ;
    }
}
