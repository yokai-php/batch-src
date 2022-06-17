<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Comment;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Yokai\Batch\Sources\Tests\Convention\Autoload;
use Yokai\Batch\Sources\Tests\Convention\Package;
use Yokai\Batch\Sources\Tests\Convention\Packages;

final class ClassCommentsTest extends TestCase
{
    /**
     * @dataProvider classes
     */
    public function testAllClassesHasComment(ReflectionClass $class): void
    {
        self::assertNotFalse(
            $class->getDocComment(),
            "{$class->getName()} must have comment."
        );
    }

    /**
     * @dataProvider classes
     */
    public function testAllSeeDocAreSurroundedWithBrackets(ReflectionClass $class): void
    {
        if (!\preg_match('/.@see [^ }]+./', (string)$class->getDocComment(), $matches)) {
            self::assertTrue(true);
            return;
        }

        self::assertStringStartsWith('{', $matches[0]);
        self::assertStringEndsWith('}', $matches[0]);
    }

    public function classes(): iterable
    {
        /** @var Package $package */
        foreach (Packages::listYokaiPackages() as $package) {
            foreach (Autoload::listAllFQCN($package->sources()) as $class) {
                if (\str_ends_with($class, 'Exception')) {
                    continue;
                }

                yield $class => [new ReflectionClass($class)];
            }
        }
    }
}
