<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Yokai\Batch\Bridge\Symfony\Uid\Factory\RandomBasedUuidJobExecutionIdGenerator;
use Yokai\Batch\Bridge\Symfony\Uid\Factory\TimeBasedUuidJobExecutionIdGenerator;
use Yokai\Batch\Bridge\Symfony\Uid\Factory\UlidJobExecutionIdGenerator;
use Yokai\Batch\Factory\UniqidJobExecutionIdGenerator;

/**
 * This is a helper for building services definitions of {@see JobExecutionIdGeneratorInterface}.
 */
final class JobExecutionIdGeneratorDefinitionFactory
{
    public const TYPES = [
        self::UNIQID,
        self::SYMFONY_RANDOM_UUID,
        self::SYMFONY_TIME_UUID,
        self::SYMFONY_ULID,
    ];
    public const DEFAULT = self::UNIQID;

    private const UNIQID = 'uniqid';
    private const SYMFONY_RANDOM_UUID = 'symfony.uuid.random';
    private const SYMFONY_TIME_UUID = 'symfony.uuid.time';
    private const SYMFONY_ULID = 'symfony.ulid';

    /**
     * Build a service definition for configured type.
     */
    public static function fromType(string $type): Definition
    {
        return match ($type) {
            self::UNIQID => self::uniqid(),
            self::SYMFONY_RANDOM_UUID => self::symfonyRandomUuid(),
            self::SYMFONY_TIME_UUID => self::symfonyTimeUuid(),
            self::SYMFONY_ULID => self::symfonyUlid(),
            default => throw new LogicException('Unsupported job job execution id generator type "' . $type . '".'),
        };
    }

    private static function uniqid(): Definition
    {
        return new Definition(UniqidJobExecutionIdGenerator::class);
    }

    private static function symfonyRandomUuid(): Definition
    {
        return new Definition(RandomBasedUuidJobExecutionIdGenerator::class, [
            '$uuidFactory' => new Reference('uuid.factory'),
        ]);
    }

    private static function symfonyTimeUuid(): Definition
    {
        return new Definition(TimeBasedUuidJobExecutionIdGenerator::class, [
            '$uuidFactory' => new Reference('uuid.factory'),
        ]);
    }

    private static function symfonyUlid(): Definition
    {
        return new Definition(UlidJobExecutionIdGenerator::class, [
            '$ulidFactory' => new Reference('ulid.factory'),
        ]);
    }
}
