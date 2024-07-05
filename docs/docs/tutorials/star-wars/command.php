<?php

namespace App\Command;

use App\Job\Import\ImportStarWarsJob;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yokai\Batch\Launcher\JobLauncherInterface;

#[AsCommand(name: 'app:import')]
final class ImportCommand extends Command
{
    public function __construct(
        private readonly JobLauncherInterface $jobLauncher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->jobLauncher->launch(ImportStarWarsJob::getJobName());

        return self::SUCCESS;
    }
}
