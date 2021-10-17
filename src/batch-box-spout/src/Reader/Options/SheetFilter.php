<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Reader\Options;

use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\SheetInterface;
use Closure;
use Generator;

/**
 *
 */
final class SheetFilter
{
    private Closure $accept;

    public function __construct(Closure $accept)
    {
        $this->accept = $accept;
    }

    public static function all(): self
    {
        return new self(fn() => true);
    }

    public static function indexIs(int $index, int ...$indexes): self
    {
        $indexes[] = $index;

        return new self(fn(SheetInterface $sheet) => \in_array($sheet->getIndex(), $indexes, true));
    }

    public static function nameIs(string $name, string ...$names): self
    {
        $names[] = $name;

        return new self(fn(SheetInterface $sheet) => \in_array($sheet->getName(), $names, true));
    }

    /**
     * @return Generator&SheetInterface[]
     * @phpstan-return Generator<SheetInterface>
     * @internal
     */
    public function getSheets(ReaderInterface $reader): Generator
    {
        foreach ($reader->getSheetIterator() as $sheet) {
            if (($this->accept)($sheet)) {
                yield $sheet;
            }
        }
    }
}
