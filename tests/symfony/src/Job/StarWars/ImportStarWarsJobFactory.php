<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars;

use Closure;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectRegistry;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectWriter;
use Yokai\Batch\Bridge\OpenSpout\Reader\FlatFileReader;
use Yokai\Batch\Bridge\OpenSpout\Reader\HeaderStrategy;
use Yokai\Batch\Bridge\Symfony\Validator\SkipInvalidItemProcessor;
use Yokai\Batch\Job\Item\ItemJob;
use Yokai\Batch\Job\Item\Processor\ArrayMapProcessor;
use Yokai\Batch\Job\Item\Processor\CallbackProcessor;
use Yokai\Batch\Job\Item\Processor\ChainProcessor;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

final readonly class ImportStarWarsJobFactory
{
    public function __construct(
        private ValidatorInterface $validator,
        private ManagerRegistry $doctrine,
        private ObjectRegistry $objectRegistry,
        private JobExecutionStorageInterface $executionStorage,
    ) {
    }

    public function create(string $file, Closure $process): JobInterface
    {
        return new ItemJob(
            50, // could be much higher, but set this way for demo purpose
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
                new CallbackProcessor(function (mixed $item) use ($process) {
                    return $process($item, $this->objectRegistry);
                }),
                new SkipInvalidItemProcessor($this->validator),
            ]),
            new ObjectWriter($this->doctrine),
            $this->executionStorage,
        );
    }
}
