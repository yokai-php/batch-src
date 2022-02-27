<?php

namespace Yokai\Batch\Bridge\Box\Spout\Writer;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

final class WriteToSheetItem
{
    private string $sheet;
    private Row $item;

    private function __construct(string $sheet, Row $item)
    {
        $this->sheet = $sheet;
        $this->item = $item;
    }

    public static function array(string $sheet, array $item, Style $style = null): self
    {
        return new self($sheet, WriterEntityFactory::createRowFromArray($item, $style));
    }

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
