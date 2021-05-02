<?php

namespace App\Job\Import;

use App\Entity\Badge;
use Yokai\Batch\Job\Item\ItemProcessorInterface;

final class ImportBadgesProcessor implements ItemProcessorInterface
{
    public function process($item)
    {
        $badge = new Badge();
        $badge->label = $item['label'];
        $badge->rank = $item['rank'];

        return $badge;
    }
}
