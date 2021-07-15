<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Job\Parameters;

use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;
use Yokai\Batch\Job\Parameters\ParentJobExecutionAccessor;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\JobExecution;

class ParentJobExecutionAccessorTest extends TestCase
{
    public function test(): void
    {
        $root = JobExecution::createRoot('123', 'root');
        $one = JobExecution::createChild($root, '1');
        $root->addChildExecution($one);
        $two = JobExecution::createChild($one, '2');
        $one->addChildExecution($two);

        $inner = $this->createMock(JobParameterAccessorInterface::class);
        $inner->method('get')->willReturnCallback(fn(JobExecution $execution) => $execution->getJobName());
        $accessor = new ParentJobExecutionAccessor($inner);

        self::assertSame('root', $accessor->get($one));
        self::assertSame('1', $accessor->get($two));
    }
}
