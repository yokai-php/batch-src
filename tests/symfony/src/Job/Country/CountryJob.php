<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Job\Country;

use Symfony\Component\HttpKernel\KernelInterface;
use Yokai\Batch\Bridge\OpenSpout\Writer\FlatFileWriter;
use Yokai\Batch\Bridge\Symfony\Framework\JobWithStaticNameInterface;
use Yokai\Batch\Job\AbstractDecoratedJob;
use Yokai\Batch\Job\Item\ElementConfiguratorTrait;
use Yokai\Batch\Job\Item\FlushableInterface;
use Yokai\Batch\Job\Item\ItemJob;
use Yokai\Batch\Job\Item\ItemProcessorInterface;
use Yokai\Batch\Job\Item\ItemWriterInterface;
use Yokai\Batch\Job\Item\Reader\AddMetadataReader;
use Yokai\Batch\Job\Item\Reader\SequenceReader;
use Yokai\Batch\Job\Item\Writer\ChainWriter;
use Yokai\Batch\Job\Item\Writer\Filesystem\JsonLinesWriter;
use Yokai\Batch\Job\Item\Writer\SummaryWriter;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

/**
 * This job will read multiple files (`data/country/*.json`)
 *
 * Merge results in memory to obtain hash with
 * - "iso2" : the ISO2 country code
 * - "iso3" : the ISO3 country code
 * - "name" : the country name
 * - "continent" : the country continent code
 * - "phone" : the country phone prefix
 *
 * Then these items will be written in multiple ways
 * - as summary variable
 *
 * - a CSV file, like :
 *   iso2,iso3,name,continent,currency,phone
 *   FR,FRA,France,EU,EUR,33
 *
 * - a JSONL file, like :
 *   {"iso2":"FR","iso3":"FRA","name":"France","continent":"EU","currency":"EUR","phone":"33"}
 */
final class CountryJob extends AbstractDecoratedJob implements
    JobWithStaticNameInterface,
    JobExecutionAwareInterface,
    ItemProcessorInterface,
    ItemWriterInterface,
    FlushableInterface
{
    use JobExecutionAwareTrait;
    use ElementConfiguratorTrait;

    private ItemWriterInterface $writer;
    private array $countries = [];
    private bool $flushed = false;

    public static function getJobName(): string
    {
        return 'country';
    }

    public function __construct(JobExecutionStorageInterface $executionStorage, KernelInterface $kernel)
    {
        $writePath = fn(string $format) => new StaticValueParameterAccessor(
            ARTIFACT_DIR . '/symfony/country/countries.' . $format
        );
        $reader = function (string $key) use ($kernel) {
            $path = new StaticValueParameterAccessor($kernel->getProjectDir() . '/data/country/' . $key . '.json');

            return new AddMetadataReader(new CountryJsonFileReader($path), ['_key' => $key]);
        };

        $fragments = ['iso3', 'name', 'continent', 'currency', 'phone'];

        $headers = \array_merge(['iso2'], $fragments);
        $this->writer = new ChainWriter([
            new SummaryWriter(new StaticValueParameterAccessor('countries')),
            new FlatFileWriter($writePath('csv'), null, null, $headers),
            new JsonLinesWriter($writePath('jsonl')),
        ]);

        parent::__construct(
            new ItemJob(
                \PHP_INT_MAX,
                new SequenceReader(\array_map($reader, $fragments)),
                $this,
                $this,
                $executionStorage
            ),
        );
    }

    public function process(mixed $item): array
    {
        return ['iso2' => $item['code'], $item['_key'] => $item['value']];
    }

    public function write(iterable $items): void
    {
        foreach ($items as $item) {
            $this->countries[$item['iso2']] = \array_merge(
                $this->countries[$item['iso2']] ?? [],
                $item
            );
        }
    }

    public function flush(): void
    {
        if ($this->flushed) {
            return;
        }

        $this->configureElementJobContext($this->writer, $this->jobExecution);
        $this->initializeElement($this->writer);
        $this->writer->write($this->countries);
        $this->flushElement($this->writer);

        $this->flushed = true;
    }
}
