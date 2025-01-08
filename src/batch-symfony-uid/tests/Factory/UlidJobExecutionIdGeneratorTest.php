<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Uid\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid;
use Yokai\Batch\Bridge\Symfony\Uid\Factory\UlidJobExecutionIdGenerator;

final class UlidJobExecutionIdGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $id = (new UlidJobExecutionIdGenerator(new UlidFactory()))->generate();

        self::assertTrue(Ulid::isValid($id));
    }
}
