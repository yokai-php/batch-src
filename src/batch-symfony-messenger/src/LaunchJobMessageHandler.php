<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Messenger;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Yokai\Batch\Launcher\JobLauncherInterface;

final class LaunchJobMessageHandler implements MessageHandlerInterface
{
    /**
     * @var JobLauncherInterface
     */
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
