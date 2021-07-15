<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Job\Parameters;

use PHPUnit\Framework\TestCase;
use Yokai\Batch\Exception\CannotAccessParameterException;
use Yokai\Batch\Job\Parameters\RootJobExecutionParameterAccessor;
use Yokai\Batch\JobExecution;
use Yokai\Batch\JobParameters;

class RootJobExecutionParameterAccessorTest extends TestCase
{
    public function test(): void
    {
        $accessor = new RootJobExecutionParameterAccessor('since');

        $root = JobExecution::createRoot('123', 'testing', null, new JobParameters(['since' => '2021-07-15']));
        $prepare = JobExecution::createChild($root, 'prepare', null, new JobParameters(['since' => 'unused']));
        $clean = JobExecution::createChild($root, 'clean');
        $root->addChildExecution($prepare);
        $root->addChildExecution($clean);

        self::assertSame('2021-07-15', $accessor->get($root));
        self::assertSame('2021-07-15', $accessor->get($prepare));
        self::assertSame('2021-07-15', $accessor->get($clean));
    }

    public function testJobParameterNotFound(): void
    {
        $this->expectException(CannotAccessParameterException::class);
        $accessor = new RootJobExecutionParameterAccessor('since');

        $root = JobExecution::createRoot('123', 'testing', null, new JobParameters(['misnamed' => '2021-07-15']));
        $child = JobExecution::createChild($root, 'child');
        $root->addChildExecution($child);

        $accessor->get($child);
    }
}
