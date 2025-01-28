<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Uid\Factory;

use Symfony\Component\Uid\Factory\UlidFactory;
use Yokai\Batch\Factory\JobExecutionIdGeneratorInterface;

/**
 * This {@see JobExecutionIdGeneratorInterface} will use
 * Symfony's {@see UlidFactory} to ULIDs.
 */
final class UlidJobExecutionIdGenerator implements JobExecutionIdGeneratorInterface
{
    public function __construct(
        private UlidFactory $ulidFactory,
    ) {
    }

    public function generate(): string
    {
        return $this->ulidFactory->create()->toBase32();
    }
}
