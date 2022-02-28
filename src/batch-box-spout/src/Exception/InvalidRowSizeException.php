<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Exception;

use Throwable;
use Yokai\Batch\Exception\LogicException;

final class InvalidRowSizeException extends LogicException
{
    /**
     * @phpstan-var array<int, string>
     */
    private array $headers;
    /**
     * @phpstan-var array<int, string>
     */
    private array $row;

    /**
     * @phpstan-param array<int, string> $headers
     * @phpstan-param array<int, string> $row
     */
    public function __construct(array $headers, array $row)
    {
        parent::__construct('Invalid row size');
        $this->headers = $headers;
        $this->row = $row;
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
