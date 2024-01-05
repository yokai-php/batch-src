<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Messenger;

use Yokai\Batch\Job\JobExecutionAccessor;
use Yokai\Batch\Job\JobExecutor;

/**
 * Answer to {@see LaunchJobMessage} and launch requested job.
 */
final class LaunchJobMessageHandler
{
    public function __construct(
        private JobExecutionAccessor $jobExecutionAccessor,
        private JobExecutor $jobExecutor,
    ) {
    }

    public function __invoke(LaunchJobMessage $message): void
    {
        $execution = $this->jobExecutionAccessor->get($message->getJobName(), $message->getConfiguration());
        $this->jobExecutor->execute($execution);
    }
}
