<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework;

use Yokai\Batch\Registry\JobRegistry;

/**
 * A job that implement this interface can define the associated job name via a static method.
 * This is very useful if you are registering jobs using PSR services registering.
 * Without implementing, the job name will be the service id,
 * in the case of a PSR service registering : the job class.
 */
interface JobWithStaticNameInterface
{
    /**
     * The job name as it will be registered in the {@see JobRegistry}.
     */
    public static function getJobName(): string;
}
