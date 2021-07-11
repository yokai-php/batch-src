<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Yokai\Batch\Routing\CallbackRouting;

class CallbackRoutingTest extends TestCase
{
    public function test(): void
    {
        $integers = new class {
        };
        $numbers = new class {
        };
        $default = new class {
        };
        $routing = new CallbackRouting([
            [fn($subject) => \is_int($subject), $integers],
            [fn($subject) => \is_numeric($subject), $numbers],
        ], $default);

        self::assertSame($integers, $routing->get(1));
        self::assertSame($integers, $routing->get(2003));
        self::assertSame($numbers, $routing->get(20.6));
        self::assertSame($numbers, $routing->get('89'));
        self::assertSame($default, $routing->get('Barney'));
        self::assertSame($default, $routing->get(false));
    }
}
