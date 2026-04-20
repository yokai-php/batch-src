<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Documentation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Factory\JobExecutionLoggerFactoryInterface;
use Yokai\Batch\Job\Item\ItemProcessorInterface;
use Yokai\Batch\Job\Item\ItemReaderInterface;
use Yokai\Batch\Job\Item\ItemWriterInterface;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Sources\Tests\Convention\Autoload;
use Yokai\Batch\Sources\Tests\Convention\Package;
use Yokai\Batch\Sources\Tests\Convention\Packages;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * Some assertions on Sphinx documentation files.
 */
final class DocumentationLinksTest extends TestCase
{
    private const DEFAULT_BRANCH = '1.x';

    /**
     * Ensure that all links in Sphinx files points to valid internal resources.
     */
    #[DataProvider('filesWithLinks')]
    public function testInternalLinksAreValid(DocFile $file): void
    {
        /** @var DocLink $link */
        foreach ($file->links as $link) {
            self::assertNotFalse(
                $link->pointsToFile->getRealPath(),
                "Link \"{$link->label}\" in \"{$link->pointsToFile->getPathname()}\"," .
                " is pointing to \"{$link->uri}\" which reference an internal file that do not exists.",
            );

            self::assertSame(self::DEFAULT_BRANCH, $link->branch, 'All links must be wired on default branch.');
        }
    }

    public static function filesWithLinks(): iterable
    {
        /** @var DocFile $file */
        foreach (Sphinx::listFiles() as $file) {
            if (\count($file->links) === 0) {
                continue;
            }
            yield $file->file->getRealPath() => [$file];
        }
    }

    /**
     * Ensure that all implementations of some yokai batch interfaces are listed in certain files.
     */
    #[DataProvider('interfaceRules')]
    public function testComponentsAreListed(string $filepath, string $interface): void
    {
        $expectedClasses = [];
        $actualClasses = [];

        // Find all classes in libraries that implement this interface
        /** @var Package $package */
        foreach (Packages::listYokaiPackages() as $package) {
            foreach (Autoload::listAllFQCN($package->sources()) as $class) {
                if ($class !== $interface && \is_a($class, $interface, true)) {
                    $expectedClasses[] = $class;
                }
            }
        }

        // Find all links in these files that points to file that implement these interfaces
        $file = Sphinx::getFile($filepath);
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
            "All classes implementing \"{$interface}\" should be linked in \"{$filepath}\".",
        );
    }

    public static function interfaceRules(): iterable
    {
        yield 'JobInterface' => [
            'docs/docs/core-concepts/job.rst',
            JobInterface::class,
        ];
        yield 'JobExecutionStorageInterface' => [
            'docs/docs/core-concepts/job-execution-storage.rst',
            JobExecutionStorageInterface::class,
        ];
        yield 'JobLauncherInterface' => [
            'docs/docs/core-concepts/job-launcher.rst',
            JobLauncherInterface::class,
        ];
        yield 'JobParameterAccessorInterface' => [
            'docs/docs/core-concepts/job-parameter-accessor.rst',
            JobParameterAccessorInterface::class,
        ];
        yield 'ItemReaderInterface' => [
            'docs/docs/core-concepts/item-job/item-reader.rst',
            ItemReaderInterface::class,
        ];
        yield 'ItemProcessorInterface' => [
            'docs/docs/core-concepts/item-job/item-processor.rst',
            ItemProcessorInterface::class,
        ];
        yield 'ItemWriterInterface' => [
            'docs/docs/core-concepts/item-job/item-writer.rst',
            ItemWriterInterface::class,
        ];
        yield 'JobExecutionLoggerFactoryInterface' => [
            'docs/docs/core-concepts/job-execution-logger.rst',
            JobExecutionLoggerFactoryInterface::class,
        ];
    }
}
