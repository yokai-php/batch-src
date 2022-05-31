<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Documentation;

use SplFileInfo;
use Symfony\Component\Finder\Finder;

final class Markdown
{
    private const MARKDOWN_LINKS_REGEX = '/\[([^]]+)\]\(([^)]+)\)/';
    private const GITHUB_BATCH_LINK_REGEX = '#https\:\/\/github\.com\/yokai\-php\/([^/]+)\/blob\/([^/]+)\/(.+)#';
    private const DEFAULT_BRANCH = '0.x';
    private const ROOT_DIR = __DIR__ . '/../../..';

    /**
     * @return iterable<DocFile>
     */
    public static function listFiles(): iterable
    {
        $files = Finder::create()->files()->in(self::ROOT_DIR . '/src/*/')->name('*.md');

        foreach ($files as $file) {
            yield new DocFile(self::getPackageFromFile($file), $file);
        }
    }

    public static function getFile(string $path): DocFile
    {
        $file = new SplFileInfo(self::ROOT_DIR . '/src/' . \ltrim($path, '/'));

        return new DocFile(self::getPackageFromFile($file), $file);
    }

    public static function listLinksInFile(DocFile $file): iterable
    {
        \preg_match_all(self::MARKDOWN_LINKS_REGEX, \file_get_contents($file->file->getRealPath()), $fileLinks);
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
                $package = $file->package;
                $branch = self::DEFAULT_BRANCH;
                $linkPath = $uri;
            }

            $linkFile = self::getFileFromMembers($package, $linkPath);

            yield new DocLink($file, $label, $package, $uri, $linkFile, $branch, $absolute);
        }
    }

    public static function getPackageFromFile(SplFileInfo $file): string
    {
        $relativePath = \str_replace(\realpath(self::ROOT_DIR) . '/src/', '', $file->getRealPath());

        return \dirname($relativePath);
    }

    public static function getFileFromMembers(string $package, string $file): SplFileInfo
    {
        return new SplFileInfo(self::ROOT_DIR . '/src/' . $package . '/' . \ltrim($file, '/'));
    }
}
