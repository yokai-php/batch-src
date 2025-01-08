<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Uid\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\UuidV6;
use Yokai\Batch\Bridge\Symfony\Uid\Factory\TimeBasedUuidJobExecutionIdGenerator;

final class TimeBasedUuidJobExecutionIdGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $id = (new TimeBasedUuidJobExecutionIdGenerator(new UuidFactory()))->generate();

        self::assertSame($id, UuidV6::fromString($id)->toString());
    }
}
