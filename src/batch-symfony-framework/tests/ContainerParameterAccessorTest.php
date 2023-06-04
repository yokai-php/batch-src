<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Yokai\Batch\Bridge\Symfony\Framework\ContainerParameterAccessor;
use Yokai\Batch\Exception\CannotAccessParameterException;
use Yokai\Batch\JobExecution;

class ContainerParameterAccessorTest extends TestCase
{
    public function test(): void
    {
        $container = new Container();
        $container->setParameter('some.parameter', 'foo');
        $accessor = new ContainerParameterAccessor($container, 'some.parameter');

        self::assertSame('foo', $accessor->get(JobExecution::createRoot('123', 'testing')));
    }

    public function testParameterNotFound(): void
    {
        $this->expectException(CannotAccessParameterException::class);
        $container = new Container();
        $container->setParameter('some.parameter', 'foo');
        $accessor = new ContainerParameterAccessor($container, 'undefined.parameter');
        $accessor->get(JobExecution::createRoot('123', 'testing'));
    }
}
