<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\OpenSpout\Exception;

use Yokai\Batch\Exception\LogicException;

final class InvalidRowSizeException extends LogicException
{
    public function __construct(
        /**
         * @var list<string>
         */
        private readonly array $headers,
        /**
         * @var list<mixed>
         */
        private readonly array $row,
    ) {
        parent::__construct('Invalid row size');
    }

    /**
     * @return list<string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return list<mixed>
     */
    public function getRow(): array
    {
        return $this->row;
    }
}
