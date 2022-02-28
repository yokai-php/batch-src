<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Reader;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Reader\ReaderInterface;
use Generator;
use Yokai\Batch\Bridge\Box\Spout\Exception\InvalidRowSizeException;
use Yokai\Batch\Bridge\Box\Spout\Reader\Options\OptionsInterface;
use Yokai\Batch\Job\Item\ItemReaderInterface;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;
use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;
use Yokai\Batch\Warning;

/**
 * This {@see ItemReaderInterface} will read from CSV/ODS/XLSX file
 * and return each line as an array.
 */
final class FlatFileReader implements
    ItemReaderInterface,
    JobExecutionAwareInterface
{
    use JobExecutionAwareTrait;

    private HeaderStrategy $headerStrategy;

    public function __construct(
        private JobParameterAccessorInterface $filePath,
        private OptionsInterface $options,
        HeaderStrategy $headerStrategy = null,
    ) {
        $this->headerStrategy = $headerStrategy ?? HeaderStrategy::skip();
    }

    /**
     * @inheritDoc
     */
    public function read(): iterable
    {
        /** @var string $path */
        $path = $this->filePath->get($this->jobExecution);

        $reader = ReaderFactory::createFromFile($path);
        $this->options->configure($reader);
        $reader->open($path);

        foreach ($this->rows($reader) as $rowIndex => $row) {
            if ($rowIndex === 1) {
                if (!$this->headerStrategy->setHeaders($row)) {
                    continue;
                }
            }

            try {
                yield $this->headerStrategy->getItem($row);
            } catch (InvalidRowSizeException $exception) {
                $this->jobExecution->addWarning(
                    new Warning(
                        'Expecting row {row} to have exactly {expected} columns(s), but got {actual}.',
                        [
                            '{row}' => (string)$rowIndex,
                            '{expected}' => (string)count($exception->getHeaders()),
                            '{actual}' => (string)count($exception->getRow()),
                        ],
                        ['headers' => $exception->getHeaders(), 'row' => $exception->getRow()]
                    )
                );
            }
        }

        $reader->close();
    }

    /**
     * @phpstan-return Generator<int, array<string>>
     */
    private function rows(ReaderInterface $reader): Generator
    {
        foreach ($this->options->getSheets($reader) as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                if ($row instanceof Row) {
                    $row = $row->toArray();
                }

                yield $rowIndex => $row;
            }
        }
    }
}
