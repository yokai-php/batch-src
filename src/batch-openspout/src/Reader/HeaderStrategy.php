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
        private string $mode,
        /**
         * @var list<string>|null
         */
        private ?array $headers
    ) {
    }

    /**
     * Read file has headers but should be skipped.
     *
     * @param list<string>|null $headers
     */
    public static function skip(array $headers = null): self
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
    public static function none(array $headers = null): self
    {
        return new self(self::NONE, $headers);
    }

    /**
     * @param list<string> $headers
     * @internal
     */
    public function setHeaders(array $headers): bool
    {
        if ($this->mode === self::NONE) {
            return true; // row should be read, will be considered as an item
        }
        if ($this->mode === self::COMBINE) {
            $this->headers = $headers;
        }

        return false; // row should be skipped, will not be considered as an item
    }

    /**
     * Build the associative item, a combination of headers and values.
     *
     * @throws InvalidRowSizeException
     *
     * @param array<int, string> $row
     *
     * @return array<int|string, string>
     * @internal
     */
    public function getItem(array $row): array
    {
        if ($this->headers === null) {
            return $row; // headers were not set, read row as is
        }

        try {
            /** @var array<string, string> $combined */
            $combined = @array_combine($this->headers, $row);
        } catch (\ValueError) {
            throw new InvalidRowSizeException($this->headers, $row);
        }

        return $combined;
    }
}
