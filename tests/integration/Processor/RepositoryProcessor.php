<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration\Processor;

use Yokai\Batch\Job\Item\ItemProcessorInterface;
use Yokai\Batch\Sources\Tests\Integration\Entity\Repository;

final class RepositoryProcessor implements ItemProcessorInterface
{
    public function process(mixed $item): Repository
    {
        $repository = new Repository();
        $repository->label = $item['label'];
        $repository->url = $item['url'];

        return $repository;
    }
}
