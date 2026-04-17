<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Form;

use Yokai\Batch\BatchStatus;
use Yokai\Batch\Storage\Query;

/**
 * Model class under the filter form.
 * Properties are to be used in a {@see Query}.
 */
final class JobFilter
{
    public function __construct(
        /**
         * @var array<string>
         */
        public array $jobs = [],
        /**
         * @var array<BatchStatus>
         */
        public array $statuses = [],
    ) {
    }
}
