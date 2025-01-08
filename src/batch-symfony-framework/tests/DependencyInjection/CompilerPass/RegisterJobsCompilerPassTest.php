<?php

declare(strict_types=1);

namespace DependencyInjection\CompilerPass;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\CompilerPass\RegisterJobsCompilerPass;
use Yokai\Batch\Exception\UndefinedJobException;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Registry\JobRegistry;
use Yokai\Batch\Tests\Bridge\Symfony\Framework\Fixtures\DummyJob;
use Yokai\Batch\Tests\Bridge\Symfony\Framework\Fixtures\DummyJobWithName;

final class RegisterJobsCompilerPassTest extends TestCase
{
    public function testProcess(): void
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

        (new RegisterJobsCompilerPass())->process($container);
        $container->compile();

        self::assertNotNull($this->getJob($container, 'export_orders_job'));
        self::assertNotNull($this->getJob($container, DummyJob::class));
        self::assertNotNull($this->getJob($container, 'job.named.with.service.id'));
        self::assertNotNull($this->getJob($container, 'job.name.in.attribute'));
    }

    private function getJob(ContainerBuilder $container, string $name): JobInterface|null
    {
        /** @var JobRegistry $registry */
        $registry = $container->get('yokai_batch.job_registry');

        try {
            return $registry->get($name);
        } catch (UndefinedJobException) {
            return null;
        }
    }
}
