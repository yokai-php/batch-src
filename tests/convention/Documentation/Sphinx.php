<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Documentation;

use Generator;
use SplFileInfo;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

/**
 * This util class is a wrapper around Symfony's finder.
 * It will search for Sphinx files considered as documentation.
 * Every fetched files will be converted to a {@see DocFile}.
 */
final class Sphinx
{
    private const SPHINX_LINKS_REGEX = '/`([^\n<]+)<([^>]+)>`__/';
    private const GITHUB_BATCH_LINK_REGEX = '#https\:\/\/github\.com\/yokai\-php\/([^/]+)\/(blob|tree)\/([^/]+)\/(.+)#';
    private const ROOT_DIR = __DIR__ . '/../../..';

    /**
     * List every known Sphinx documentation files in yokai batch packages.
     *
     * @return iterable<DocFile>
     */
    public static function listFiles(): iterable
    {
        $files = Finder::create()->files()->in(self::ROOT_DIR . '/docs/docs/*/')->name('*.rst');

        foreach ($files as $file) {
            yield self::createDocFile($file);
        }
    }

    /**
     * Convert a Sphinx documentation file to a {@see DocFile}.
     */
    public static function getFile(string $path): DocFile
    {
        return self::createDocFile(new SplFileInfo(self::ROOT_DIR . '/' . \ltrim($path, '/')));
    }

    private static function createDocFile(SplFileInfo $file): DocFile
    {
        $links = \iterator_to_array(self::listLinksInFile($file->getRealPath()));

        return new DocFile($file, $links);
    }

    /**
     * @return Generator<DocLink>
     */
    private static function listLinksInFile(string $path): Generator
    {
        \preg_match_all(self::SPHINX_LINKS_REGEX, \file_get_contents($path), $fileLinks);
        foreach (\array_keys($fileLinks[0]) as $idx) {
            $label = \trim($fileLinks[1][$idx]);
            $uri = $fileLinks[2][$idx];
            $absolute = \str_starts_with($uri, 'https://') || \str_starts_with($uri, 'http://');
            if ($absolute) {
                if (!\preg_match(self::GITHUB_BATCH_LINK_REGEX, $uri, $internalLink)) {
                    continue; // it's an external link
                }
                [, $package, , $branch, $linkPath] = $internalLink;
            } else {
                throw new \LogicException($fileLinks[0][$idx]);//todo
            }

            $linkFile = self::getFileFromMembers($package, $linkPath);

            yield new DocLink($label, $uri, $linkFile, $branch);
        }
    }

    private static function getFileFromMembers(string $package, string $file): SplFileInfo
    {
        if ($package === 'batch-src') {
            return new SplFileInfo(Path::canonicalize(self::ROOT_DIR . '/' . \ltrim($file, '/')));
        }

        return new SplFileInfo(Path::canonicalize(self::ROOT_DIR . '/src/' . $package . '/' . \ltrim($file, '/')));
    }
}
