<?php

namespace App\Command;

use App\Entity\Family;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectWriter;
use Yokai\Batch\Bridge\Symfony\Validator\SkipInvalidItemProcessor;
use Yokai\Batch\Job\Item\ItemJob;
use Yokai\Batch\Job\Item\Processor\CallbackProcessor;
use Yokai\Batch\Job\Item\Processor\ChainProcessor;
use Yokai\Batch\Job\Item\Reader\Filesystem\JsonLinesReader;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\NullJobExecutionStorage;

#[AsCommand(name: 'app:import')]
final class ImportCommand extends Command
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly ManagerRegistry $doctrine,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new ItemJob(
            batchSize: 500,
            reader: new JsonLinesReader(new StaticValueParameterAccessor(__DIR__ . '/families.jsonl')),
            processor: new ChainProcessor([
                new CallbackProcessor(fn(array $data) => Family::fromData($data)),
                new SkipInvalidItemProcessor($this->validator),
            ]),
            writer: new ObjectWriter($this->doctrine),
            executionStorage: new NullJobExecutionStorage(),
        ))->execute(JobExecution::createRoot('1', 'import'));

        return self::SUCCESS;
    }
}
