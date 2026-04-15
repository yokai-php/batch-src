<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\OpenSpout\Reader;

use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\OpenSpout\Reader\SheetFilter;

final class SheetFilterTest extends TestCase
{
    private const MULTI_TABS = __DIR__ . '/fixtures/multi-tabs.xlsx';

    public function testAllAcceptsEverySheet(): void
    {
        $reader = new XLSXReader();
        $reader->open(self::MULTI_TABS);

        $sheets = \array_values(\iterator_to_array(SheetFilter::all()->list($reader)));
        $reader->close();

        self::assertCount(2, $sheets);
        self::assertSame(0, $sheets[0]->getIndex());
        self::assertSame(1, $sheets[1]->getIndex());
    }

    public function testIndexIsFiltersToSingleIndex(): void
    {
        $reader = new XLSXReader();
        $reader->open(self::MULTI_TABS);

        $sheets = \array_values(\iterator_to_array(SheetFilter::indexIs(1)->list($reader)));
        $reader->close();

        self::assertCount(1, $sheets);
        self::assertSame(1, $sheets[0]->getIndex());
    }

    public function testIndexIsFiltersToMultipleIndexes(): void
    {
        $reader = new XLSXReader();
        $reader->open(self::MULTI_TABS);

        $sheets = \array_values(\iterator_to_array(SheetFilter::indexIs(0, 1)->list($reader)));
        $reader->close();

        self::assertCount(2, $sheets);
        self::assertSame(0, $sheets[0]->getIndex());
        self::assertSame(1, $sheets[1]->getIndex());
    }

    public function testNameIsFiltersToMatchingSheet(): void
    {
        $reader = new XLSXReader();
        $reader->open(self::MULTI_TABS);

        $sheets = \array_values(\iterator_to_array(SheetFilter::nameIs('Français')->list($reader)));
        $reader->close();

        self::assertCount(1, $sheets);
        self::assertSame('Français', $sheets[0]->getName());
    }

    public function testNameIsFiltersToMultipleNames(): void
    {
        $reader = new XLSXReader();
        $reader->open(self::MULTI_TABS);

        // Retrieve both sheet names to avoid hardcoding the first sheet's name
        $allSheets = \array_values(\iterator_to_array(SheetFilter::all()->list($reader)));
        $reader->close();

        $firstName = $allSheets[0]->getName();

        $reader = new XLSXReader();
        $reader->open(self::MULTI_TABS);

        $sheets = \array_values(\iterator_to_array(SheetFilter::nameIs($firstName, 'Français')->list($reader)));
        $reader->close();

        self::assertCount(2, $sheets);
    }

    public function testFilterWithNoMatch(): void
    {
        $reader = new XLSXReader();
        $reader->open(self::MULTI_TABS);

        $sheets = \iterator_to_array(SheetFilter::nameIs('NonExistent')->list($reader));
        $reader->close();

        self::assertCount(0, $sheets);
    }
}
