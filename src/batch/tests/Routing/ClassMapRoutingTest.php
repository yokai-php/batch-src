<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Routing;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Throwable;
use TypeError;
use Yokai\Batch\Routing\ClassMapRouting;

class ClassMapRoutingTest extends TestCase
{
    public function test(): void
    {
        $dates = new class {
        };
        $exceptions = new class {
        };
        $default = new class {
        };
        $routing = new ClassMapRouting([DateTimeInterface::class => $dates, Throwable::class => $exceptions], $default);

        self::assertSame($dates, $routing->get(new DateTime()));
        self::assertSame($dates, $routing->get(new DateTimeImmutable()));
        self::assertSame($exceptions, $routing->get(new Exception()));
        self::assertSame($exceptions, $routing->get(new TypeError()));
        self::assertSame($default, $routing->get('string'));
        self::assertSame($default, $routing->get(123));
    }
}
