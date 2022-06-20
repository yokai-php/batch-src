<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Reader\Options;

use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\SheetInterface;
use Closure;
use Generator;

/**
 * A sheet filter is used by {@see XLSXOptions} & {@see ODSOptions}
 * so you can tell which sheet is to be read.
 */
final class SheetFilter
{
    private Closure $accept;

    /**
     * @param Closure $accept A closure with {@see SheetInterface} as single argument,
     *                        and returning a boolean, telling if the sheet should be read.
     */
    public function __construct(Closure $accept)
    {
        $this->accept = $accept;
    }

    /**
     * Will read every sheets in file.
     */
    public static function all(): self
    {
        return new self(fn() => true);
    }

    /**
     * Will read sheets that are at specified indexes.
     */
    public static function indexIs(int $index, int ...$indexes): self
    {
        $indexes[] = $index;

        return new self(fn(SheetInterface $sheet) => \in_array($sheet->getIndex(), $indexes, true));
    }

    /**
     * Will read sheets that are named as specified.
     */
    public static function nameIs(string $name, string ...$names): self
    {
        $names[] = $name;

        return new self(fn(SheetInterface $sheet) => \in_array($sheet->getName(), $names, true));
    }

    /**
     * Iterate over valid sheets for the provided filter.
     *
     * @return Generator&SheetInterface[]
     * @phpstan-return Generator<SheetInterface>
     * @internal
     */
    public function getSheets(ReaderInterface $reader): Generator
    {
        /** @var SheetInterface $sheet */
        foreach ($reader->getSheetIterator() as $sheet) {
            if (($this->accept)($sheet)) {
                yield $sheet;
            }
        }
    }
}
