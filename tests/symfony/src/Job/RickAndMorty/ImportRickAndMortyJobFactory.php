<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty;

use Closure;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectWriter;
use Yokai\Batch\Bridge\Symfony\Validator\SkipInvalidItemProcessor;
use Yokai\Batch\Job\Item\ItemJob;
use Yokai\Batch\Job\Item\Processor\ArrayMapProcessor;
use Yokai\Batch\Job\Item\Processor\CallbackProcessor;
use Yokai\Batch\Job\Item\Processor\ChainProcessor;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

final readonly class ImportRickAndMortyJobFactory
{
    public function __construct(
        private ValidatorInterface $validator,
        private ManagerRegistry $doctrine,
        private ImportRickAndMortyMemory $memory,
        private JobExecutionStorageInterface $executionStorage,
    ) {
    }

    public function create(string $api, array $mocks, Closure $process): JobInterface
    {
        return new ItemJob(
            20, // same as API pagination
            new RickAndMortyApiReader(
                // we are using MockHttpClient because it is a test, you can use an actual client instead
                new MockHttpClient(\array_map(
                    fn(string $file) => new MockResponse(\file_get_contents($file)),
                    $mocks,
                )),
                $api,
            ),
            new ChainProcessor([
                new ArrayMapProcessor(
                    fn(mixed $value) => $value === '' ? null : $value,
                ),
                new CallbackProcessor(fn(mixed $item) => $process($item, $this->memory)),
                new SkipInvalidItemProcessor($this->validator),
            ]),
            new ObjectWriter($this->doctrine),
            $this->executionStorage,
        );
    }
}
