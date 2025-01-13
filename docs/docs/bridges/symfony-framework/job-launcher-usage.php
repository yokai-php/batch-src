<?php

declare(strict_types=1);

namespace App\Service;

use Yokai\Batch\Launcher\JobLauncherInterface;

final class YourAppCode
{
    public function __construct(
        private JobLauncherInterface $jobLauncher, // will inject the default job launcher
        private JobLauncherInterface $simpleJobLauncher, // will inject the "simple" job launcher
        private JobLauncherInterface $messengerJobLauncher, // will inject the "messenger" job launcher
    ) {
    }
}
