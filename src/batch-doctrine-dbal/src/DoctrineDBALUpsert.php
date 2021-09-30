<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\DBAL;

/**
 * This model object is used by {@see DoctrineDBALUpsertWriter}.
 * It holds table, data and identity to perform upsert operation.
 */
final class DoctrineDBALUpsert
{
    private string $table;

    /**
     * @phpstan-var array<string, mixed>
     */
    private array $data;

    /**
     * @phpstan-var array<string, mixed>
     */
    private array $identity;

    /**
     * @phpstan-param array<string, mixed> $data
     * @phpstan-param array<string, mixed> $identity
     */
    public function __construct(string $table, array $data, array $identity = [])
    {
        $this->table = $table;
        $this->data = $data;
        $this->identity = $identity;
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
