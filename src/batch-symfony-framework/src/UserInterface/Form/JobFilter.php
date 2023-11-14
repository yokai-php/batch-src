<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Form;

use Yokai\Batch\Storage\Query;

/**
 * Model class under the filter form.
 * Properties are to be used in a {@see Query}.
 */
final class JobFilter
{
    /**
     * @var array<string>
     */
    public array $jobs = [];

    /**
     * @var array<int>
     */
    public array $statuses = [];

    /**
     * @param array<string> $jobs
     * @param array<int>    $statuses
     */
    public function __construct(array $jobs = [], array $statuses = [])
    {
        $this->jobs = $jobs;
        $this->statuses = $statuses;
    }
}
