<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Target;
use Yokai\Batch\Launcher\JobLauncherInterface;

final class YourAppCode
{
    public function __construct(
        private JobLauncherInterface $jobLauncher, // will inject the default job launcher
        #[Target('simpleJobLauncher')]
        private JobLauncherInterface $simpleJobLauncher, // will inject the "simple" job launcher
        #[Target('messengerJobLauncher')]
        private JobLauncherInterface $messengerJobLauncher, // will inject the "messenger" job launcher
    ) {
    }
}
