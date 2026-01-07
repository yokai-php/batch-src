<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Comment;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Yokai\Batch\Sources\Tests\Convention\Autoload;
use Yokai\Batch\Sources\Tests\Convention\Package;
use Yokai\Batch\Sources\Tests\Convention\Packages;

final class ClassCommentsTest extends CommentsTestCase
{
    #[DataProvider('classes')]
    public function testAllClassesHasComment(ReflectionClass $class): void
    {
        self::assertNotFalse(
            $class->getDocComment(),
            "{$class->getName()} must have comment.",
        );
    }

    #[DataProvider('classes')]
    public function testAllSeeDocAreSurroundedWithBrackets(ReflectionClass $class): void
    {
        self::assertAllSeeDocAreSurroundedWithBrackets((string)$class->getDocComment());
    }

    public static function classes(): iterable
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
