<?php

namespace App\Job\Import;

use Closure;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectWriter;
use Yokai\Batch\Bridge\OpenSpout\Reader\FlatFileReader;
use Yokai\Batch\Bridge\OpenSpout\Reader\HeaderStrategy;
use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Bridge\Symfony\Validator\SkipInvalidItemProcessor;
use Yokai\Batch\Job\AbstractDecoratedJob;
use Yokai\Batch\Job\Item\ItemJob;
use Yokai\Batch\Job\Item\Processor\ArrayMapProcessor;
use Yokai\Batch\Job\Item\Processor\CallbackProcessor;
use Yokai\Batch\Job\Item\Processor\ChainProcessor;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

abstract class AbstractImportStartWarsEntityJob extends AbstractDecoratedJob implements JobWithStaticNameInterface
{
    public function __construct(
        string $file,
        Closure $process,
        ValidatorInterface $validator,
        ManagerRegistry $doctrine,
        JobExecutionStorageInterface $executionStorage,
    ) {
        parent::__construct(
            new ItemJob(
                50, // could be much higher, but you usually have to play around that value
                new FlatFileReader(
                    new StaticValueParameterAccessor($file),
                    null,
                    null,
                    HeaderStrategy::combine(),
                ),
                new ChainProcessor([
                    new ArrayMapProcessor(
                        fn(string $value) => $value === 'NA' ? null : $value,
                    ),
                    new CallbackProcessor($process),
                    new SkipInvalidItemProcessor($validator),
                ]),
                new ObjectWriter($doctrine),
                $executionStorage,
            ),
        );
    }
}
