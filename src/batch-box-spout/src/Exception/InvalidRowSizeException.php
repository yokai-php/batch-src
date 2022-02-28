<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Exception;

use Yokai\Batch\Exception\LogicException;

final class InvalidRowSizeException extends LogicException
{
    public function __construct(
        /**
         * @phpstan-var array<int, string>
         */
        private array $headers,
        /**
         * @phpstan-var array<int, string>
         */
        private array $row,
    ) {
        parent::__construct('Invalid row size');
    }

    /**
     * @phpstan-return array<int, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @phpstan-return array<int, string>
     */
    public function getRow(): array
    {
        return $this->row;
    }
}
