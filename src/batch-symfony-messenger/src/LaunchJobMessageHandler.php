<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Messenger;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Yokai\Batch\Launcher\JobLauncherInterface;

/**
 * Answer to {@see LaunchJobMessage} and launch requested job.
 */
final class LaunchJobMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private JobLauncherInterface $jobLauncher,
    ) {
    }

    public function __invoke(LaunchJobMessage $message): void
    {
        $this->jobLauncher->launch($message->getJobName(), $message->getConfiguration());
    }
}
