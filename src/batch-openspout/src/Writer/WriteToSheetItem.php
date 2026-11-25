<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\OpenSpout\Writer;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;

/**
 * This model object is used by {@see FlatFileWriter}.
 * It holds sheet and row to write to file.
 */
final readonly class WriteToSheetItem
{
    private function __construct(
        private string $sheet,
        private Row $item,
    ) {
    }

    /**
     * Static constructor from array data.
     *
     * @param list<bool|\DateInterval|\DateTimeInterface|float|int|string> $item
     */
    public static function array(string $sheet, array $item, Style|null $style = null): self
    {
        if ($style === null) {
            return new self($sheet, Row::fromValues($item));
        }

        // OpenSpout 5 removed row styles: the style is applied to each cell instead
        // @phpstan-ignore function.alreadyNarrowedType (method was added in openspout/openspout 5.0, and we support ^4.0)
        if (\method_exists(Row::class, 'fromValuesWithStyle')) {
            return new self($sheet, Row::fromValuesWithStyle($item, $style));
        }

        // OpenSpout 4: the second argument of Row::fromValues() is the row style
        // @phpstan-ignore argument.type (in openspout/openspout 5.0, the second argument became the row height)
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
