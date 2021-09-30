<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yokai\Batch\Bridge\Symfony\Framework\YokaiBatchBundle;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Registry\JobRegistry;
use Yokai\Batch\Tests\Bridge\Symfony\Framework\Fixtures\DummyJob;
use Yokai\Batch\Tests\Bridge\Symfony\Framework\Fixtures\DummyJobWithName;

class YokaiBatchBundleTest extends TestCase
{
    public function testBuild(): void
    {
        $container = new ContainerBuilder();
        $container->register('yokai_batch.job_registry', JobRegistry::class)
            ->setPublic(true);

        $container->register(DummyJobWithName::class, DummyJobWithName::class)
            ->addTag('yokai_batch.job');
        $container->register(DummyJob::class, DummyJob::class)
            ->addTag('yokai_batch.job');
        $container->register('job.named.with.service.id', DummyJob::class)
            ->addTag('yokai_batch.job');
        $container->register('job.named.with.tag.attribute', DummyJob::class)
            ->addTag('yokai_batch.job', ['job' => 'job.name.in.attribute']);

        (new YokaiBatchBundle())->build($container);

        $container->compile();
        $registry = $container->get('yokai_batch.job_registry');
        self::assertInstanceOf(JobInterface::class, $registry->get('export_orders_job'));
        self::assertInstanceOf(JobInterface::class, $registry->get(DummyJob::class));
        self::assertInstanceOf(JobInterface::class, $registry->get('job.named.with.service.id'));
        self::assertInstanceOf(JobInterface::class, $registry->get('job.name.in.attribute'));
    }
}
