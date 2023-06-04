<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Item\ItemWriterInterface;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;
use Yokai\Batch\Warning;

/**
 * This {@see ItemWriterInterface} will insert/update items to one or multiple tables,
 * via a Doctrine {@see Connection}.
 * All items must instance of {@see DoctrineDBALUpsert}.
 */
final class DoctrineDBALUpsertWriter implements
    ItemWriterInterface,
    JobExecutionAwareInterface
{
    use JobExecutionAwareTrait;

    private Connection $connection;

    public function __construct(ConnectionRegistry $doctrine, string $connection = null)
    {
        $connection ??= $doctrine->getDefaultConnectionName();
        /** @var Connection $connection */
        $connection = $doctrine->getConnection($connection);
        $this->connection = $connection;
    }

    
    public function write(iterable $items): void
    {
        foreach ($items as $item) {
            if (!$item instanceof DoctrineDBALUpsert) {
                throw UnexpectedValueException::type(DoctrineDBALUpsert::class, $item);
            }

            // if identity provided, try to update
            if ($item->getIdentity() !== []) {
                $affected = $this->connection->update($item->getTable(), $item->getData(), $item->getIdentity());
                if ($affected > 0) {
                    if ($affected > 1) {
                        $this->jobExecution->addWarning(
                            new Warning(
                                'Update affected more than one line.',
                                [],
                                ['table' => $item->getTable(), 'identity' => $item->getIdentity(), 'count' => $affected]
                            )
                        );
                    }

                    continue; // update succeed
                }
            }

            // either no identity provided or update failed
            $this->connection->insert($item->getTable(), $item->getData());
        }
    }
}
