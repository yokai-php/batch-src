<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\DBAL\Dummy;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use InvalidArgumentException;

final readonly class SingleConnectionRegistry implements ConnectionRegistry
{
    public function __construct(
        private Connection $connection,
        private string $name = 'default',
    ) {
    }

    public function getDefaultConnectionName(): string
    {
        return $this->name;
    }

    public function getConnection(string|null $name = null): object
    {
        if ($name === $this->name) {
            return $this->connection;
        }

        throw new InvalidArgumentException(\sprintf('Doctrine Connection named "%s" does not exist.', $name));
    }

    public function getConnections(): array
    {
        return [$this->connection];
    }

    public function getConnectionNames(): array
    {
        return [$this->name];
    }
}
