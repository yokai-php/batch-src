<?php

declare(strict_types=1);

namespace Yokai\Batch;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * @template-implements IteratorAggregate<string, mixed>
 */
final class Summary implements
    Countable,
    IteratorAggregate
{
    /**
     * @phpstan-var array<string, mixed>
     */
    private array $values;

    /**
     * @phpstan-param array<string, mixed> $values
     */
    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    /**
     * @param string $key
     * @param mixed  $info
     */
    public function set(string $key, $info): void
    {
        $this->values[$key] = $info;
    }

    /**
     * @param string    $key
     * @param int|float $increment
     */
    public function increment(string $key, $increment = 1): void
    {
        $this->values[$key] = ($this->values[$key] ?? 0) + $increment;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function get(string $key)
    {
        return $this->values[$key] ?? null;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->values[$key]);
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    public function all(): array
    {
        return $this->values;
    }

    public function clear(): void
    {
        $this->values = [];
    }

    /**
     * @inheritdoc
     * @phpstan-return ArrayIterator<string, mixed>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->values);
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->values);
    }
}
