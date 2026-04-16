<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Yokai\Batch\Bridge\Doctrine\DBAL\DoctrineDBALJobExecutionStorage;
use Yokai\Batch\Serializer\JsonJobExecutionSerializer;
use Yokai\Batch\Storage\FilesystemJobExecutionStorage;

/**
 * This is a helper for building services definitions of {@see JobExecutionStorageInterface}.
 */
final class StorageDefinitionFactory
{
    /**
     * Build a service definition from DSN string.
     */
    public static function fromDsn(string $dsn): Definition|Reference
    {
        $parsed = Dsn::parse($dsn);
        $type = $parsed->getScheme();

        return match ($type) {
            'filesystem' => self::filesystem($parsed),
            'dbal' => self::dbal($parsed),
            'service' => self::service($parsed),
            default => throw new LogicException('Unsupported job execution storage type "' . $type . '".'),
        };
    }

    private static function filesystem(Dsn $dsn): Definition
    {
        $dir = $dsn->getHost() . $dsn->getPath();
        if ($dir === '') {
            $dir = '%kernel.project_dir%/var/batch';
        }

        return new Definition(FilesystemJobExecutionStorage::class, [
            new Reference($dsn->getOption('serializer', JsonJobExecutionSerializer::class)),
            $dir,
        ]);
    }

    private static function dbal(Dsn $dsn): Definition
    {
        $connection = $dsn->getHost() ?: null;
        $table = $dsn->getOption('table');
        if ($table === 'null') {
            $table = null;
        }

        return new Definition(DoctrineDBALJobExecutionStorage::class, [
            new Reference('doctrine'),
            [
                'connection' => $connection,
                'table' => $table,
            ],
        ]);
    }

    private static function service(Dsn $dsn): Reference
    {
        $id = $dsn->getOption('id') ?? throw new LogicException(
            'Missing "id" parameter to configure the job execution storage.',
        );

        return new Reference($id);
    }
}
