<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Comment;

use ReflectionClass;
use Yokai\Batch\Sources\Tests\Convention\Autoload;
use Yokai\Batch\Sources\Tests\Convention\Package;
use Yokai\Batch\Sources\Tests\Convention\Packages;

final class ClassCommentsTest extends CommentsTestCase
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
        self::assertAllSeeDocAreSurroundedWithBrackets((string)$class->getDocComment());
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
