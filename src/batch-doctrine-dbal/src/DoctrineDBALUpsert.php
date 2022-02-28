<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\DBAL;

/**
 * This model object is used by {@see DoctrineDBALUpsertWriter}.
 * It holds table, data and identity to perform upsert operation.
 */
final class DoctrineDBALUpsert
{
    public function __construct(
        private string $table,
        /**
         * @phpstan-var array<string, mixed>
         */
        private array $data,
        /**
         * @phpstan-var array<string, mixed>
         */
        private array $identity = [],
    ) {
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getIdentity(): array
    {
        return $this->identity;
    }
}
