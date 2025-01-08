<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Uid\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\UuidV4;
use Yokai\Batch\Bridge\Symfony\Uid\Factory\RandomBasedUuidJobExecutionIdGenerator;

final class RandomBasedUuidJobExecutionIdGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $id = (new RandomBasedUuidJobExecutionIdGenerator(new UuidFactory()))->generate();

        self::assertSame($id, UuidV4::fromString($id)->toString());
    }
}
