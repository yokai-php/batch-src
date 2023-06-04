<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Comment;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Yokai\Batch\Sources\Tests\Convention\Autoload;
use Yokai\Batch\Sources\Tests\Convention\Package;
use Yokai\Batch\Sources\Tests\Convention\Packages;

final class MethodCommentsTest extends CommentsTestCase
{
    /**
     * @dataProvider publicMethods
     */
    public function testAllPublicMethodsHasComment(ReflectionMethod $method): void
    {
        // Only true comments are relevant, phpdoc is not.
        $lines = \explode(\PHP_EOL, $method->getDocComment() ?: '');
        $lines = \array_map(fn($line) => \trim($line, '/* '), $lines);
        $lines = \array_filter($lines, fn($line) => !\str_starts_with($line, '@'));
        $lines = \array_filter($lines);
        $comment = \implode(\PHP_EOL, $lines);

        self::assertNotEmpty(
            $comment,
            "{$this->methodFQCN($method)} is a public method and must have comment." .
            " In {$this->fileAndLine($method)}."
        );
    }

    /**
     * @dataProvider publicMethods
     */
    public function testAllSeeDocAreSurroundedWithBrackets(ReflectionMethod $method): void
    {
        self::assertAllSeeDocAreSurroundedWithBrackets((string)$method->getDocComment());
    }

    public function publicMethods(): iterable
    {
        $magicMethods = \array_fill_keys(['__construct', '__invoke'], true);

        /** @var Package $package */
        foreach (Packages::listYokaiPackages() as $package) {
            foreach (Autoload::listAllFQCN($package->sources()) as $class) {
                if (\str_ends_with($class, 'Exception')) {
                    continue;
                }

                $class = new ReflectionClass($class);

                $methodsFromInterfaces = [];
                foreach ($class->getInterfaces() as $interface) {
                    foreach ($interface->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                        $methodsFromInterfaces[$method->getName()] = true;
                    }
                }
                $accessorMethods = [];
                foreach ($class->getProperties() as $property) {
                    $accessorMethods[$property->getName()] = true;
                    $propertyName = \ucfirst($property->getName());
                    foreach (['get', 'set', 'is'] as $prefix) {
                        $accessorMethods[$prefix . $propertyName] = true;
                    }
                }

                foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->getDeclaringClass()->getName() !== $class->getName()) {
                        continue;
                    }
                    if (isset($magicMethods[$method->getName()])) {
                        // you should understand what magic methods are used for
                        continue;
                    }
                    if (isset($methodsFromInterfaces[$method->getName()])) {
                        // methods inherited from interface will have comment at interface level
                        continue;
                    }
                    if (isset($accessorMethods[$method->getName()])) {
                        // you should understand what accessor methods are used for
                        continue;
                    }

                    yield $this->methodFQCN($method) => [$method];
                }
            }
        }
    }

    private function methodFQCN(ReflectionMethod $method): string
    {
        return "{$method->getDeclaringClass()->getName()}::{$method->getName()}";
    }

    private function fileAndLine(ReflectionMethod $method): string
    {
        return "{$method->getDeclaringClass()->getFileName()}:{$method->getStartLine()}";
    }
}
