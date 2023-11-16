<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Dependency;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;
use Yokai\Batch\Sources\Tests\Convention\Package;
use Yokai\Batch\Sources\Tests\Convention\Packages;

/**
 * Perform some tests over root repository dependencies.
 */
final class SourcesTest extends TestCase
{
    /**
     * This root repository should have aggregated dependencies from each individual packages.
     */
    public function test(): void
    {
        $relative = fn(string $path) => Path::makeRelative($path, __DIR__ . '/../../..') . '/';

        $expectedReplace = [];
        $expectedProdDeps = [];
        $expectedDevDeps = [];
        $expectedProdAutoload = [];
        $expectedDevAutoload = [];
        /** @var Package $package */
        foreach (Packages::listYokaiPackages() as $package) {
            $expectedProdDeps = \array_merge($expectedProdDeps, $package->composer->packages());
            $expectedDevDeps = \array_merge($expectedDevDeps, $package->composer->packagesDev());
            $expectedReplace[$package->name] = 'self.version';
            $expectedProdAutoload[$package->namespace()] = $relative($package->sources());
            $expectedDevAutoload[$package->testsNamespace()] = $relative($package->tests());
        }

        $rootComposer = Packages::getRootComposer();

        $expectedProdDeps = \array_unique($expectedProdDeps);
        \sort($expectedProdDeps);
        $prodDeps = $rootComposer->packages();
        $prodDeps[] = 'yokai/batch';
        \sort($prodDeps);
        self::assertSame(
            [],
            \array_diff($expectedProdDeps, $prodDeps),
            'Dependencies of all packages are required in root composer.json'
        );

        $expectedDevDeps = \array_unique($expectedDevDeps);
        \sort($expectedDevDeps);
        $devDeps = $rootComposer->packagesDev();
        // regarding packages, this should be a "require-dev" dependency,
        // but because Symfony framework expect it in "require" to register related services,
        // we are required to add it in "require"
        $devDeps[] = 'symfony/form';
        \sort($devDeps);
        self::assertSame(
            [],
            \array_diff($expectedDevDeps, $devDeps),
            'Dev dependencies of all packages are required in root composer.json'
        );

        $replace = $rootComposer->replace();
        \ksort($replace);
        \ksort($expectedReplace);
        self::assertSame(
            $expectedReplace,
            $replace,
            'All packages are replaced in root composer.json'
        );

        $prodAutoload = $rootComposer->autoload();
        \ksort($prodAutoload);
        \ksort($expectedProdAutoload);
        self::assertSame(
            [],
            \array_diff($expectedProdAutoload, $prodAutoload),
            'All packages autoload rules are duplicated in root composer.json'
        );

        $devAutoload = $rootComposer->autoloadDev();
        \ksort($devAutoload);
        \ksort($expectedDevAutoload);
        self::assertSame(
            [],
            \array_diff($expectedDevAutoload, $devAutoload),
            'All packages dev autoload rules are duplicated in root composer.json'
        );
    }
}
