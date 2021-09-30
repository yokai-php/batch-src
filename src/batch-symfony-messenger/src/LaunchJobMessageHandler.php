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
    private JobLauncherInterface $jobLauncher;

    public function __construct(JobLauncherInterface $jobLauncher)
    {
        $this->jobLauncher = $jobLauncher;
    }

    public function __invoke(LaunchJobMessage $message): void
    {
        $this->jobLauncher->launch($message->getJobName(), $message->getConfiguration());
    }
}
