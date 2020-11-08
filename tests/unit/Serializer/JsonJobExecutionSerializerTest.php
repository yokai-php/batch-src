<?php

namespace Yokai\Batch\Tests\Unit\Serializer;

use PHPUnit\Framework\TestCase;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Serializer\JsonJobExecutionSerializer;

class JsonJobExecutionSerializerTest extends TestCase
{
    /**
     * @dataProvider sets
     */
    public function testSerialize(JobExecution $jobExecutionToSerialize, string $expectedSerializedJobExecution): void
    {
        $serializer = new JsonJobExecutionSerializer();
        self::assertSame($expectedSerializedJobExecution, $serializer->serialize($jobExecutionToSerialize));
    }

    /**
     * @dataProvider sets
     */
    public function testDenormalize(JobExecution $expectedjobExecution, string $serializedJobExecution): void
    {
        $serializer = new JsonJobExecutionSerializer();
        self::assertEquals(
            $expectedjobExecution,
            $serializer->unserialize($serializedJobExecution)
        );
    }

    public function sets(): \Generator
    {
        yield [
            require __DIR__ . '/fixtures/minimal.object.php',
            \json_encode(require __DIR__ . '/fixtures/minimal.array.php'),
        ];
        yield [
            require __DIR__ . '/fixtures/fulfilled.object.php',
            \json_encode(require __DIR__ . '/fixtures/fulfilled.array.php'),
        ];
    }
}
