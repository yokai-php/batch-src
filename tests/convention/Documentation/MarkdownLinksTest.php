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
     * Ensure that all links in markdown files points to valid internal resources.
     *
     * @dataProvider filesWithLinks
     */
    public function testInternalLinksAreValid(DocFile $file): void
    {
        /** @var DocLink $link */
        foreach ($file->links as $link) {
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

    public function filesWithLinks(): iterable
    {
        /** @var DocFile $file */
        foreach (Markdown::listFiles() as $file) {
            if (\count($file->links) === 0) {
                continue;
            }
            yield $file->file->getRealPath() => [$file];
        }
    }

    /**
     * Ensure that all implementations of some yokai batch interfaces are listed in certain files.
     *
     * @dataProvider interfaceRules
     */
    public function testComponentsAreListed(string $filepath, string $interface): void
    {
        $expectedClasses = [];
        $actualClasses = [];

        // Find all classes in libraries that implement this interface
        foreach (Autoload::listPackageDirs() as $dir) {
            foreach (Autoload::listAllFQCN($dir) as $class) {
                if ($class !== $interface && \is_a($class, $interface, true)) {
                    $expectedClasses[] = $class;
                }
            }
        }

        // Find all links in these files that points to file that implement these interfaces
        $file = Markdown::getFile($filepath);
        /** @var DocLink $link */
        foreach ($file->links as $link) {
            if (!\str_ends_with($link->uri, '.php')) {
                continue; // it's not a php file the link is pointing to
            }
            $class = Autoload::getFQCN($link->pointsToFile->getRealPath());
            if ($class !== $interface && \is_a($class, $interface, true)) {
                $actualClasses[] = $class;
            }
        }

        // Classes are sorted before being compared
        \sort($expectedClasses);
        \sort($actualClasses);

        // Compare classes implementing these interfaces with listed classes in file
        self::assertSame(
            $expectedClasses,
            $actualClasses,
            "All classes implementing \"{$interface}\" should be linked in \"{$filepath}\"."
        );
    }

    public function interfaceRules(): iterable
    {
        yield 'JobInterface' => [
            'batch/docs/domain/job.md',
            JobInterface::class,
        ];
        yield 'JobExecutionStorageInterface' => [
            'batch/docs/domain/job-execution-storage.md',
            JobExecutionStorageInterface::class,
        ];
        yield 'JobLauncherInterface' => [
            'batch/docs/domain/job-launcher.md',
            JobLauncherInterface::class,
        ];
        yield 'JobParameterAccessorInterface' => [
            'batch/docs/domain/job-parameter-accessor.md',
            JobParameterAccessorInterface::class,
        ];
        yield 'ItemReaderInterface' => [
            'batch/docs/domain/item-job/item-reader.md',
            ItemReaderInterface::class,
        ];
        yield 'ItemProcessorInterface' => [
            'batch/docs/domain/item-job/item-processor.md',
            ItemProcessorInterface::class,
        ];
        yield 'ItemWriterInterface' => [
            'batch/docs/domain/item-job/item-writer.md',
            ItemWriterInterface::class,
        ];
    }
}
