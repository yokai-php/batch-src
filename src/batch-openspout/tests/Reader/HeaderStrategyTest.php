<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\OpenSpout\Reader;

use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\OpenSpout\Exception\InvalidRowSizeException;
use Yokai\Batch\Bridge\OpenSpout\Reader\HeaderStrategy;

final class HeaderStrategyTest extends TestCase
{
    public function testNoneMode(): void
    {
        $strategy = HeaderStrategy::none();

        // In none mode: setHeaders returns true (row is an item)
        self::assertTrue($strategy->setHeaders(['col1', 'col2']));

        // In none mode without predefined headers: getItem returns row as-is
        $row = ['foo', 'bar'];
        self::assertSame($row, $strategy->getItem($row));
    }

    public function testNoneModeWithPredefinedHeaders(): void
    {
        $strategy = HeaderStrategy::none(['prenom', 'nom']);

        // In none mode: setHeaders returns true (row is an item)
        self::assertTrue($strategy->setHeaders(['ignored', 'headers']));

        // In none mode with predefined headers: getItem combines headers with row
        $row = ['John', 'Doe'];
        self::assertSame(['prenom' => 'John', 'nom' => 'Doe'], $strategy->getItem($row));
    }

    public function testSkipMode(): void
    {
        $strategy = HeaderStrategy::skip();

        // In skip mode: setHeaders returns false (row should be skipped)
        self::assertFalse($strategy->setHeaders(['firstName', 'lastName']));

        // In skip mode without predefined headers: getItem returns row as-is
        $row = ['John', 'Doe'];
        self::assertSame($row, $strategy->getItem($row));
    }

    public function testSkipModeWithPredefinedHeaders(): void
    {
        $strategy = HeaderStrategy::skip(['prenom', 'nom']);

        // In skip mode: setHeaders returns false (row should be skipped)
        self::assertFalse($strategy->setHeaders(['ignored', 'headers']));

        // In skip mode with predefined headers: getItem combines headers with row
        $row = ['Jean', 'Dupont'];
        self::assertSame(['prenom' => 'Jean', 'nom' => 'Dupont'], $strategy->getItem($row));
    }

    public function testCombineMode(): void
    {
        $strategy = HeaderStrategy::combine();

        // In combine mode: setHeaders returns false (header row should be skipped)
        self::assertFalse($strategy->setHeaders(['firstName', 'lastName']));

        // In combine mode: getItem combines stored headers with row values
        $row = ['John', 'Doe'];
        self::assertSame(['firstName' => 'John', 'lastName' => 'Doe'], $strategy->getItem($row));
    }

    public function testCombineModeConvertsHeadersToString(): void
    {
        $strategy = HeaderStrategy::combine();

        // Even with non-string header values, they get converted to string
        $strategy->setHeaders([42, 3.14]);

        $row = ['foo', 'bar'];
        self::assertSame(['42' => 'foo', '3.14' => 'bar'], $strategy->getItem($row));
    }

    public function testGetItemThrowsOnSizeMismatch(): void
    {
        $strategy = HeaderStrategy::combine();
        $strategy->setHeaders(['firstName', 'lastName']);

        $this->expectException(InvalidRowSizeException::class);
        $strategy->getItem(['OnlyOneValue']);
    }

    public function testGetItemSizeMismatchExceptionContainsData(): void
    {
        $strategy = HeaderStrategy::combine();
        $strategy->setHeaders(['firstName', 'lastName']);

        try {
            $strategy->getItem(['OnlyOne']);
            self::fail('Expected exception was not thrown');
        } catch (InvalidRowSizeException $exception) {
            self::assertSame(['firstName', 'lastName'], $exception->getHeaders());
            self::assertSame(['OnlyOne'], $exception->getRow());
        }
    }
}
