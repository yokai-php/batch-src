<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention;

/**
 * Access packages in projet.
 */
final class Packages
{
    /**
     * List all yokai batch packages.
     *
     * @return iterable<Package>
     */
    public static function listYokaiPackages(): iterable
    {
        foreach (self::getRootComposer()->autoload() as $dir) {
            yield new Package(\dirname(__DIR__ . '/../../' . $dir));
        }
    }

    /**
     * Create a package object based on the name (from sources or vendor).
     */
    public static function getPackage(string $name): Package
    {
        if (\str_starts_with($name, 'yokai/')) {
            return new Package(__DIR__ . '/../../src/' . \explode('/', $name)[1]);
        }

        return new Package(__DIR__ . '/../../vendor/' . $name);
    }

    /**
     * Build object from composer.json in root dir.
     */
    public static function getRootComposer(): Composer
    {
        return new Composer(__DIR__ . '/../../composer.json');
    }
}
