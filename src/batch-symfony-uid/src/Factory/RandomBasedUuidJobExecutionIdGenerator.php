<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Uid\Factory;

use Symfony\Component\Uid\Factory\UuidFactory;
use Yokai\Batch\Factory\JobExecutionIdGeneratorInterface;

/**
 * This {@see JobExecutionIdGeneratorInterface} will use
 * Symfony's {@see UuidFactory} to generate random based UUIDs.
 */
final class RandomBasedUuidJobExecutionIdGenerator implements JobExecutionIdGeneratorInterface
{
    public function __construct(
        private UuidFactory $uuidFactory,
    ) {
    }

    public function generate(): string
    {
        return $this->uuidFactory->randomBased()->create()->toString();
    }
}
