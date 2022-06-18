<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Writer;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

/**
 * This model object is used by {@see FlatFileWriter}.
 * It holds sheet and row to write to file.
 */
final class WriteToSheetItem
{
    private function __construct(
        private string $sheet,
        private Row $item
    ) {
    }

    /**
     * Static constructor from array data.
     *
     * @param array<int|string, mixed> $item
     */
    public static function array(string $sheet, array $item, Style $style = null): self
    {
        return new self($sheet, WriterEntityFactory::createRowFromArray($item, $style));
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
