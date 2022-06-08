<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Dependency;

use PHPUnit\Framework\TestCase;
use Yokai\Batch\Sources\Tests\Convention\Autoload;
use Yokai\Batch\Sources\Tests\Convention\Packages;
use Yokai\Batch\Sources\Tests\Convention\Package;

final class PackagesTest extends TestCase
{
    /**
     * Every individual package must declare explicit dependencies, based on use statements.
     *
     * @dataProvider packages
     */
    public function testPackagesAreUsingRequiredClasses(Package $package): void
    {
        // Read package's composer.json and extract autoload prefixes of
        $requirePrefixes = [
            $package->namespace(), // Package can use itself
            'Composer\\', // Package can use Composer introspection
            'PHPUnit\\', // Package may provide test dummies in sources
        ];
        foreach (\array_merge($package->composer->packages(), $package->composer->suggest()) as $require) {
            if (!\str_contains($require, '/')) {
                continue; // php & extensions
            }
            $requirePackage = Packages::getPackage($require);
            foreach (\array_keys($requirePackage->composer->autoload()) as $prefix) {
                $requirePrefixes[] = $prefix;
            }
        }

        foreach (Autoload::listFiles($package->sources()) as $path) {
            \preg_match_all('/^use ([^;]+\\\\[^;]+);$/m', \file_get_contents($path), $matches);
            foreach ($matches[1] as $use) {
                foreach ($requirePrefixes as $prefix) {
                    if (\str_starts_with($use, $prefix)) {
                        self::assertTrue(true);
                        continue 2;
                    }
                }
                self::fail(Autoload::getFQCN($path) . ' is using ' . $use . ' which is not in a required package');
            }
        }
    }

    /**
     * If packages are referencing same dependencies, versions should be same.
     */
    public function testPackagesAreUsingSameDependencyVersions(): void
    {
        $requirements = [];
        /** @var Package $package */
        foreach (Packages::listYokaiPackages() as $package) {
            foreach ($package->composer->require() as $dependency => $version) {
                $requirements[$dependency] ??= [];
                $requirements[$dependency][] = $version;
            }
        }

        foreach ($requirements as $dependency => $versions) {
            $distinctVersions = \array_values(\array_unique($versions));
            self::assertCount(
                1,
                $distinctVersions,
                "{$dependency} is configured on the same version : " . \json_encode($distinctVersions)
            );
        }
    }

    public function packages(): iterable
    {
        /** @var Package $package */
        foreach (Packages::listYokaiPackages() as $package) {
            yield $package->name => [$package];
        }
    }
}
