<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Documentation;

use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * This util class is a wrapper around Symfony's finder.
 * It will search for markdown files considered as documentation.
 * Every fetched files will be converted to a {@see DocFile}.
 */
final class Markdown
{
    private const MARKDOWN_LINKS_REGEX = '/\[([^]]+)\]\(([^)]+)\)/';
    private const GITHUB_BATCH_LINK_REGEX = '#https\:\/\/github\.com\/yokai\-php\/([^/]+)\/blob\/([^/]+)\/(.+)#';
    private const DEFAULT_BRANCH = '0.x';
    private const ROOT_DIR = __DIR__ . '/../../..';

    /**
     * List every known markdown documentation files in yokai batch packages.
     *
     * @return iterable<DocFile>
     */
    public static function listFiles(): iterable
    {
        $files = Finder::create()->files()->in(self::ROOT_DIR . '/src/*/')->name('*.md');

        foreach ($files as $file) {
            yield self::createDocFile($file);
        }
    }

    /**
     * Convert a markdown documentation file to a {@see DocFile}.
     */
    public static function getFile(string $path): DocFile
    {
        return self::createDocFile(new SplFileInfo(self::ROOT_DIR . '/src/' . \ltrim($path, '/')));
    }

    private static function createDocFile(SplFileInfo $file): DocFile
    {
        $package = self::getPackageFromFile($file);

        return new DocFile($package, $file, self::listLinksInFile($file->getRealPath(), $package));
    }

    /**
     * @return iterable<DocLink>
     */
    private static function listLinksInFile(string $path, string $filePackage): iterable
    {
        \preg_match_all(self::MARKDOWN_LINKS_REGEX, \file_get_contents($path), $fileLinks);
        foreach (\array_keys($fileLinks[0]) as $idx) {
            $label = $fileLinks[1][$idx];
            $uri = $fileLinks[2][$idx];
            if (\str_starts_with($label, '!')) {
                continue; // it's an image
            }

            $absolute = \str_starts_with($uri, 'https://') || \str_starts_with($uri, 'http://');
            if ($absolute) {
                if (!\preg_match(self::GITHUB_BATCH_LINK_REGEX, $uri, $internalLink)) {
                    continue; // it's an external link
                }
                [, $package, $branch, $linkPath] = $internalLink;
            } else {
                $package = $filePackage;
                $branch = self::DEFAULT_BRANCH;
                $linkPath = $uri;
            }

            $linkFile = self::getFileFromMembers($package, $linkPath);

            yield new DocLink($label, $package, $uri, $linkFile, $branch, $absolute);
        }
    }

    private static function getPackageFromFile(SplFileInfo $file): string
    {
        $relativePath = \str_replace(\realpath(self::ROOT_DIR) . '/src/', '', $file->getRealPath());

        return \dirname($relativePath);
    }

    private static function getFileFromMembers(string $package, string $file): SplFileInfo
    {
        return new SplFileInfo(self::ROOT_DIR . '/src/' . $package . '/' . \ltrim($file, '/'));
    }
}
