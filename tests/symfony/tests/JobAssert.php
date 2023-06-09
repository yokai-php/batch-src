<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\Tests;

use PHPUnit\Framework\Assert;
use Yokai\Batch\JobExecution;

final class JobAssert
{
    public static function assertIsSuccessful(JobExecution $execution): void
    {
        Assert::assertTrue(
            $execution->getStatus()->isSuccessful(),
            'Failed asserting the job succeed. Failure:' . PHP_EOL . ($execution->getAllFailures()[0] ?? null),
        );
    }

    public static function assertItemJobStats(
        JobExecution $execution,
        int $read,
        int $processed,
        int $write,
        int $skipped = null
    ): void {
        Assert::assertSame($read, $execution->getSummary()->get('read'));
        Assert::assertSame($processed, $execution->getSummary()->get('processed'));
        Assert::assertSame($write, $execution->getSummary()->get('write'));
        Assert::assertSame($skipped, $execution->getSummary()->get('skipped'));
    }
}
