<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yokai\Batch\Storage\JobExecutionStorageInterface;
use Yokai\Batch\Storage\SetupableJobExecutionStorageInterface;

/**
 * Prepare the required infrastructure for the job execution storage.
 */
#[AsCommand(name: 'yokai:batch:setup-storage', description: 'Prepare the required infrastructure for the storage')]
final class SetupStorageCommand extends Command
{
    public function __construct(
        private JobExecutionStorageInterface $storage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command setups the job execution storage:

    <info>php %command.full_name%</info>
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->storage instanceof SetupableJobExecutionStorageInterface) {
            $this->storage->setup();
            $io->success('The storage was set up successfully.');
        } else {
            $io->note('The storage does not support setup.');
        }

        return self::SUCCESS;
    }
}
