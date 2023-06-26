<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\OpenSpout\Reader;

use Generator;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Reader\CSV\Options as CSVOptions;
use OpenSpout\Reader\CSV\Reader as CSVReader;
use OpenSpout\Reader\ODS\Options as ODSOptions;
use OpenSpout\Reader\ODS\Reader as ODSReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Options as XLSXOptions;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use Yokai\Batch\Bridge\OpenSpout\Exception\InvalidRowSizeException;
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

    private SheetFilter $sheetFilter;
    private HeaderStrategy $headerStrategy;

    public function __construct(
        private JobParameterAccessorInterface $filePath,
        private CSVOptions|ODSOptions|XLSXOptions|null $options = null,
        SheetFilter $sheetFilter = null,
        HeaderStrategy $headerStrategy = null,
    ) {
        $this->sheetFilter = $sheetFilter ?? SheetFilter::all();
        $this->headerStrategy = $headerStrategy ?? HeaderStrategy::none();
    }

    public function read(): iterable
    {
        /** @var string $path */
        $path = $this->filePath->get($this->jobExecution);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $reader = match ($extension) {
            'csv' => new CSVReader($this->options),
            'xlsx' => new XLSXReader($this->options),
            'ods' => new ODSReader($this->options),
            default => throw new UnsupportedTypeException('No readers supporting the given type: ' . $extension),
        };

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
                        sprintf(
                            'Expecting row %s to have exactly %d columns(s), but got %d.',
                            $rowIndex,
                            count($exception->getHeaders()),
                            count($exception->getRow()),
                        ),
                        [],
                        ['headers' => $exception->getHeaders(), 'row' => $exception->getRow()]
                    )
                );
            }
        }

        $reader->close();
    }

    /**
     * @return Generator<int, array<null|bool|string|int|float|\DateTimeInterface|\DateInterval>>
     */
    private function rows(ReaderInterface $reader): Generator
    {
        foreach ($this->sheetFilter->list($reader) as $sheet) {
            /** @var int $rowIndex */
            /** @var Row $row */
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                yield $rowIndex => $row->toArray();
            }
        }
    }
}
