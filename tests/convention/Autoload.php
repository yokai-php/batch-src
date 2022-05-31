<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention;

use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * This util class allows for autoloading introspection using filesystem.
 * It is used to discover interfaces/classes in filesystem and list some.
 */
final class Autoload
{
    /**
     * List all FQCNs in a filesystem path.
     *
     * @return iterable<class-string>
     */
    public static function listAllFQCN(string $path): iterable
    {
        foreach (self::listFiles($path) as $file) {
            yield self::getFQCN($file);
        }
    }

    /**
     * Return the FQCN of the class living in a file.
     *
     * @return class-string
     */
    public static function getFQCN(string $file): string
    {
        return self::getNamespace($file) . '\\' . self::getClassname($file);
    }

    /**
     * List all dirs where yokai batch packages are living.
     *
     * @return iterable<string>
     */
    public static function listPackageDirs(): iterable
    {
        $composer = \json_decode(\file_get_contents(__DIR__ . '/../../composer.json'), true);

        foreach ($composer['autoload']['psr-4'] as $dir) {
            yield __DIR__ . '/../../' . $dir;
        }
    }

    private static function listFiles(string $path): iterable
    {
        $files = Finder::create()->files()->in($path)->name('*.php');
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            yield $file->getRealpath();
        }
    }

    private static function getNamespace(string $filename): string
    {
        \preg_match('/namespace (.*);/', \file_get_contents($filename), $namespace);

        return $namespace[1] ?? throw new \Exception('No namespace');
    }

    private static function getClassname(string $filename): string
    {
        return \basename($filename, '.php');
    }

}
