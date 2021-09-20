<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Job\Item\Reader;

use ArrayIterator;
use Generator;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Job\Item\Reader\IndexWithReader;
use Yokai\Batch\Job\Item\Reader\StaticIterableReader;

class IndexWithReaderTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function test(IndexWithReader $reader, array $expected): void
    {
        $actual = [];
        foreach ($reader->read() as $index => $item) {
            $actual[$index] = $item;
        }

        self::assertSame($expected, $actual);
    }

    public function provider(): Generator
    {
        $john = ['name' => 'John', 'location' => 'Washington'];
        $marie = ['name' => 'Marie', 'location' => 'London'];
        yield 'Index with array key' => [
            IndexWithReader::withArrayKey(
                new StaticIterableReader([$john, $marie]),
                'name'
            ),
            ['John' => $john, 'Marie' => $marie],
        ];

        $john = (object)$john;
        $marie = (object)$marie;
        yield 'Index with object property' => [
            IndexWithReader::withProperty(
                new StaticIterableReader([$john, $marie]),
                'name'
            ),
            ['John' => $john, 'Marie' => $marie],
        ];

        $three = new ArrayIterator([1, 2, 3]);
        $six = new ArrayIterator([1, 2, 3, 4, 5, 6]);
        yield 'Index with object method' => [
            IndexWithReader::withGetter(
                new StaticIterableReader([$three, $six]),
                'count'
            ),
            [3 => $three, 6 => $six],
        ];

        yield 'Index with arbitrary closure' => [
            new IndexWithReader(
                new StaticIterableReader([1, 2, 3]),
                fn(int $value) => $value * $value
            ),
            [1 => 1, 4 => 2, 9 => 3],
        ];
    }
}
