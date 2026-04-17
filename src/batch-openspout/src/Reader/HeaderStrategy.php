<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\OpenSpout\Reader;

use Yokai\Batch\Bridge\OpenSpout\Exception\InvalidRowSizeException;

/**
 * Strategies for handling flat files headers :
 * - File as header, but you don't care : {@see HeaderStrategy::skip}
 * - File as header, and you want each item to be indexed with : {@see HeaderStrategy::combine}
 * - File as no header : {@see HeaderStrategy::none}
 */
final class HeaderStrategy
{
    private const SKIP = 'skip';
    private const COMBINE = 'combine';
    private const NONE = 'none';

    private function __construct(
        private readonly string $mode,
        /**
         * @var list<string>|null
         */
        private null|array $headers,
    ) {
    }

    /**
     * Read file has headers but should be skipped.
     *
     * @param list<string>|null $headers
     */
    public static function skip(array|null $headers = null): self
    {
        return new self(self::SKIP, $headers);
    }

    /**
     * Read file has headers and should be used to array_combine each row.
     */
    public static function combine(): self
    {
        return new self(self::COMBINE, null);
    }

    /**
     * Read file has no headers.
     *
     * @param list<string>|null $headers
     */
    public static function none(array|null $headers = null): self
    {
        return new self(self::NONE, $headers);
    }

    /**
     * Process a row from the file.
     * Returns null if the row should be skipped (e.g. it is a header row).
     * Returns an array if the row should be yielded as an item.
     *
     * @param list<string> $row
     *
     * @return array<string, string>|list<string>|null
     *
     * @throws InvalidRowSizeException
     */
    public function process(array $row, bool $isFirstRow): array|null
    {
        if ($isFirstRow) {
            if ($this->mode === self::COMBINE) {
                $this->headers = $row;

                return null;
            }
            if ($this->mode === self::SKIP) {
                return null;
            }
            // NONE mode: fall through and treat first row as a regular item
        }

        if ($this->headers === null) {
            return $row;
        }

        try {
            /** @var array<string, string> $combined */
            $combined = @\array_combine($this->headers, $row);
        } catch (\ValueError) {
            throw new InvalidRowSizeException($this->headers, $row);
        }

        return $combined;
    }
}
