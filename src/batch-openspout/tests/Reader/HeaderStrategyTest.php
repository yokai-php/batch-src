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

        // In none mode: first row is not skipped, it is treated as a regular item
        self::assertSame(['col1', 'col2'], $strategy->process(['col1', 'col2'], true));

        // Subsequent rows are returned as-is
        $row = ['foo', 'bar'];
        self::assertSame($row, $strategy->process($row, false));
    }

    public function testNoneModeWithPredefinedHeaders(): void
    {
        $strategy = HeaderStrategy::none(['prenom', 'nom']);

        // In none mode with predefined headers: first row is combined with those headers
        self::assertSame(['prenom' => 'ignored', 'nom' => 'headers'], $strategy->process(['ignored', 'headers'], true));

        // Subsequent rows are also combined with the predefined headers
        self::assertSame(['prenom' => 'John', 'nom' => 'Doe'], $strategy->process(['John', 'Doe'], false));
    }

    public function testSkipMode(): void
    {
        $strategy = HeaderStrategy::skip();

        // In skip mode: first row is skipped (null returned)
        self::assertNull($strategy->process(['firstName', 'lastName'], true));

        // Subsequent rows are returned as-is (no headers to combine with)
        $row = ['John', 'Doe'];
        self::assertSame($row, $strategy->process($row, false));
    }

    public function testSkipModeWithPredefinedHeaders(): void
    {
        $strategy = HeaderStrategy::skip(['prenom', 'nom']);

        // In skip mode: first row is always skipped regardless of predefined headers
        self::assertNull($strategy->process(['ignored', 'headers'], true));

        // Subsequent rows are combined with the predefined headers
        self::assertSame(['prenom' => 'Jean', 'nom' => 'Dupont'], $strategy->process(['Jean', 'Dupont'], false));
    }

    public function testCombineMode(): void
    {
        $strategy = HeaderStrategy::combine();

        // In combine mode: first row is skipped and stored as headers
        self::assertNull($strategy->process(['firstName', 'lastName'], true));

        // Subsequent rows are combined with the stored headers
        self::assertSame(['firstName' => 'John', 'lastName' => 'Doe'], $strategy->process(['John', 'Doe'], false));
    }

    public function testCombineModeConvertsHeadersToString(): void
    {
        $strategy = HeaderStrategy::combine();

        // Non-string header values are converted to strings via array_combine
        self::assertNull($strategy->process([42, 3.14], true));

        $row = ['foo', 'bar'];
        self::assertSame(['42' => 'foo', '3.14' => 'bar'], $strategy->process($row, false));
    }

    public function testProcessThrowsOnSizeMismatch(): void
    {
        $strategy = HeaderStrategy::combine();
        $strategy->process(['firstName', 'lastName'], true);

        $this->expectException(InvalidRowSizeException::class);
        $strategy->process(['OnlyOneValue'], false);
    }

    public function testProcessSizeMismatchExceptionContainsData(): void
    {
        $strategy = HeaderStrategy::combine();
        $strategy->process(['firstName', 'lastName'], true);

        try {
            $strategy->process(['OnlyOne'], false);
            self::fail('Expected exception was not thrown');
        } catch (InvalidRowSizeException $exception) {
            self::assertSame(['firstName', 'lastName'], $exception->getHeaders());
            self::assertSame(['OnlyOne'], $exception->getRow());
        }
    }
}
