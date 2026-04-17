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
        $sheets = $this->filterSheets(SheetFilter::all());

        self::assertCount(2, $sheets);
        self::assertSame(0, $sheets[0]->getIndex());
        self::assertSame(1, $sheets[1]->getIndex());
    }

    public function testIndexIsFiltersToSingleIndex(): void
    {
        $sheets = $this->filterSheets(SheetFilter::indexIs(1));

        self::assertCount(1, $sheets);
        self::assertSame(1, $sheets[0]->getIndex());
    }

    public function testIndexIsFiltersToMultipleIndexes(): void
    {
        $sheets = $this->filterSheets(SheetFilter::indexIs(0, 1));

        self::assertCount(2, $sheets);
        self::assertSame(0, $sheets[0]->getIndex());
        self::assertSame(1, $sheets[1]->getIndex());
    }

    public function testNameIsFiltersToMatchingSheet(): void
    {
        $sheets = $this->filterSheets(SheetFilter::nameIs('Français'));

        self::assertCount(1, $sheets);
        self::assertSame('Français', $sheets[0]->getName());
    }

    public function testNameIsFiltersToMultipleNames(): void
    {
        // Retrieve first sheet name dynamically to avoid hardcoding it
        $allSheets = $this->filterSheets(SheetFilter::all());
        $firstName = $allSheets[0]->getName();

        $sheets = $this->filterSheets(SheetFilter::nameIs($firstName, 'Français'));

        self::assertCount(2, $sheets);
    }

    public function testFilterWithNoMatch(): void
    {
        $sheets = $this->filterSheets(SheetFilter::nameIs('NonExistent'));

        self::assertCount(0, $sheets);
    }

    private function filterSheets(SheetFilter $filter): array
    {
        $reader = new XLSXReader();
        $reader->open(self::MULTI_TABS);

        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            if ($filter->accepts($sheet)) {
                $sheets[] = $sheet;
            }
        }

        $reader->close();

        return $sheets;
    }
}
