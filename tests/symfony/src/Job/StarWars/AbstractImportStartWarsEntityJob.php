<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars;

use Closure;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Bridge\Box\Spout\Reader\FlatFileReader;
use Yokai\Batch\Bridge\Box\Spout\Reader\HeaderStrategy;
use Yokai\Batch\Bridge\Box\Spout\Reader\Options\CSVOptions;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectWriter;
use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Bridge\Symfony\Validator\SkipInvalidItemProcessor;
use Yokai\Batch\Job\Item\ItemJob;
use Yokai\Batch\Job\Item\Processor\ArrayMapProcessor;
use Yokai\Batch\Job\Item\Processor\CallbackProcessor;
use Yokai\Batch\Job\Item\Processor\ChainProcessor;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * Abstract Star Wars entity import.
 * This job is an {@see ItemJob} : using a read / process / write component.
 *
 * Reader
 *  - Read a CSV file with header (see `data/star-wars/*.csv`).
 *
 * Processor :
 * - Converts all NA value in items to NULL.
 * - Transform items to entity using a callback.
 * - Validates each item and skip invalid ones.
 *
 * Writer :
 * - Write processed entities to the database.
 */
abstract class AbstractImportStartWarsEntityJob extends ItemJob implements JobWithStaticNameInterface
{
    public function __construct(
        string $file,
        Closure $process,
        ValidatorInterface $validator,
        ManagerRegistry $doctrine,
        JobExecutionStorageInterface $executionStorage
    ) {
        parent::__construct(
            50, // could be much higher, but set this way for demo purpose
            new FlatFileReader(
                new StaticValueParameterAccessor($file),
                new CSVOptions(),
                HeaderStrategy::combine()
            ),
            new ChainProcessor([
                new ArrayMapProcessor(
                    fn(string $value) => $value === 'NA' ? null : $value
                ),
                new CallbackProcessor($process),
                new SkipInvalidItemProcessor($validator),
            ]),
            new ObjectWriter($doctrine),
            $executionStorage
        );
    }
}
