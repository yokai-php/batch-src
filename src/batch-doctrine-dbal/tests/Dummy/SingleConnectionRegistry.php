<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\DBAL\Dummy;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use InvalidArgumentException;

final class SingleConnectionRegistry implements ConnectionRegistry
{
    public function __construct(
        private Connection $connection,
        private string $name = 'default',
    ) {
    }

    public function getDefaultConnectionName()
    {
        return $this->name;
    }

    public function getConnection($name = null)
    {
        if ($name === $this->name) {
            return $this->connection;
        }

        throw new InvalidArgumentException(sprintf('Doctrine Connection named "%s" does not exist.', $name));
    }

    public function getConnections()
    {
        return [$this->connection];
    }

    public function getConnectionNames()
    {
        return [$this->name];
    }
}
