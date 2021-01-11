<?php

namespace App\Job\Import;

use App\Entity\Repository;
use Yokai\Batch\Job\Item\ItemProcessorInterface;

final class ImportRepositoriesProcessor implements ItemProcessorInterface
{
    public function process($item)
    {
        $repository = new Repository();
        $repository->label = $item['label'];
        $repository->url = $item['url'];

        return $repository;
    }
}
