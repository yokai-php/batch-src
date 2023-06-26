<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\OpenSpout\Writer;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;

/**
 * This model object is used by {@see FlatFileWriter}.
 * It holds sheet and row to write to file.
 */
final class WriteToSheetItem
{
    private function __construct(
        private string $sheet,
        private Row $item,
    ) {
    }

    /**
     * Static constructor from array data.
     *
     * @param array<int, bool|\DateInterval|\DateTimeInterface|float|int|string> $item
     */
    public static function array(string $sheet, array $item, Style $style = null): self
    {
        return new self($sheet, Row::fromValues($item, $style));
    }

    /**
     * Static constructor from {@see Row} object.
     */
    public static function row(string $sheet, Row $item): self
    {
        return new self($sheet, $item);
    }

    public function getSheet(): string
    {
        return $this->sheet;
    }

    public function getItem(): Row
    {
        return $this->item;
    }
}
