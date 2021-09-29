<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework;

/**
 * A job that implement this interface can define the associated job name via a static method.
 * This is very useful if you are registering jobs using PSR services registering.
 * Without implementing, the job name will be the service id,
 * in the case of a PSR service registering : the job class.
 */
interface JobWithStaticNameInterface
{
    public static function getJobName(): string;
}
