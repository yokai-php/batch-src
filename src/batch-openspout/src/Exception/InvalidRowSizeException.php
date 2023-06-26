<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\OpenSpout\Exception;

use Yokai\Batch\Exception\LogicException;

final class InvalidRowSizeException extends LogicException
{
    public function __construct(
        /**
         * @var array<int, string>
         */
        private array $headers,
        /**
         * @var array<int, string>
         */
        private array $row,
    ) {
        parent::__construct('Invalid row size');
    }

    /**
     * @return array<int, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array<int, string>
     */
    public function getRow(): array
    {
        return $this->row;
    }
}
