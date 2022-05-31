<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Documentation;

use PHPUnit\Framework\TestCase;
use Yokai\Batch\Job\Item\ItemProcessorInterface;
use Yokai\Batch\Job\Item\ItemReaderInterface;
use Yokai\Batch\Job\Item\ItemWriterInterface;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Sources\Tests\Convention\Autoload;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * Some assertions on markdown documentation files.
 */
final class MarkdownLinksTest extends TestCase
{
    private const DEFAULT_BRANCH = '0.x';

    /**
     * Ensure that all links in markdown files points to valid interal resources.
     */
    public function testInternalLinksAreValid(): void
    {
        /** @var DocFile $file */
        foreach (Markdown::listFiles() as $file) {
            foreach (Markdown::listLinksInFile($file) as $link) {
                if ($link->package !== $file->package) {
                    self::assertTrue($link->absolute, 'When pointing to another package, links must be absolute.');
                }

                self::assertNotFalse(
                    $link->pointsToFile->getRealPath(),
                    "Link \"{$link->label}\" in \"{$link->pointsToFile->getRealPath()}\"," .
                    " is pointing to \"{$link->uri}\" which reference an internal file that do not exists."
                );

                self::assertSame(self::DEFAULT_BRANCH, $link->branch, 'All links must be wired on default branch.');
            }
        }
    }

    /**
     * Ensure that all implementations of some yokai batch interfaces are listed in certain files.
     */
    public function testComponentsAreListed(): void
    {
        // All rules of components listing must be defined here
        $rules = [
            'batch/docs/domain/job.md' => JobInterface::class,
            'batch/docs/domain/job-execution-storage.md' => JobExecutionStorageInterface::class,
            'batch/docs/domain/job-launcher.md' => JobLauncherInterface::class,
            'batch/docs/domain/job-parameter-accessor.md' => JobParameterAccessorInterface::class,
            'batch/docs/domain/item-job/item-reader.md' => ItemReaderInterface::class,
            'batch/docs/domain/item-job/item-processor.md' => ItemProcessorInterface::class,
            'batch/docs/domain/item-job/item-writer.md' => ItemWriterInterface::class,
        ];

        $actualClassesForInterface = $expectedClassesForInterface = \array_fill_keys($rules, []);

        // Find all classes in libraries that implement these interfaces
        foreach (Autoload::listPackageDirs() as $dir) {
            foreach (Autoload::listAllFQCN($dir) as $class) {
                foreach ($rules as $interface) {
                    if ($class !== $interface && \is_a($class, $interface, true)) {
                        $expectedClassesForInterface[$interface][] = $class;
                    }
                }
            }
        }

        // Find all links in these files that points to file that implement these interfaces
        foreach ($rules as $filepath => $interface) {
            $file = Markdown::getFile($filepath);
            foreach (Markdown::listLinksInFile($file) as $link) {
                if (!\str_ends_with($link->uri, '.php')) {
                    continue; // it's not a php file the link is pointing to
                }
                $class = Autoload::getFQCN($link->pointsToFile->getRealPath());
                if ($class !== $interface && \is_a($class, $interface, true)) {
                    $actualClassesForInterface[$interface][] = $class;
                }
            }
        }

        // Classes are sorted before being compared
        foreach ($expectedClassesForInterface as &$classes) {
            \sort($classes);
        }
        foreach ($actualClassesForInterface as &$classes) {
            \sort($classes);
        }

        // Compare classes implementing these interfaces with listed classes in file
        foreach ($rules as $file => $interface) {
            self::assertSame(
                $expectedClassesForInterface[$interface],
                $actualClassesForInterface[$interface],
                "All classes implementing \"{$interface}\" should be linked in \"{$file}\"."
            );
        }
    }
}
