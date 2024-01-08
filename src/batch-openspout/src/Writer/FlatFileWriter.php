<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\OpenSpout\Writer;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Writer\AbstractWriterMultiSheets;
use OpenSpout\Writer\CSV\Options as CSVOptions;
use OpenSpout\Writer\CSV\Writer as CSVWriter;
use OpenSpout\Writer\ODS\Options as ODSOptions;
use OpenSpout\Writer\ODS\Writer as ODSWriter;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Options as XLSXOptions;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use Yokai\Batch\Exception\BadMethodCallException;
use Yokai\Batch\Exception\RuntimeException;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Item\FlushableInterface;
use Yokai\Batch\Job\Item\InitializableInterface;
use Yokai\Batch\Job\Item\ItemWriterInterface;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;
use Yokai\Batch\Job\Parameters\JobParameterAccessorInterface;

/**
 * This {@see ItemWriterInterface} will write to CSV/ODS/XLSX file
 * and each item will written its own line.
 */
final class FlatFileWriter implements
    ItemWriterInterface,
    JobExecutionAwareInterface,
    InitializableInterface,
    FlushableInterface
{
    use JobExecutionAwareTrait;

    private ?WriterInterface $writer = null;
    private bool $headersAdded = false;

    public function __construct(
        private JobParameterAccessorInterface $filePath,
        private CSVOptions|ODSOptions|XLSXOptions|null $options = null,
        private string|null $defaultSheet = null,
        /**
         * @var list<string>|null
         */
        private ?array $headers = null,
    ) {
    }

    public function initialize(): void
    {
        /** @var string $path */
        $path = $this->filePath->get($this->jobExecution);
        $dir = \dirname($path);
        if (!@\is_dir($dir) && !@\mkdir($dir, 0777, true)) {
            throw new RuntimeException(\sprintf('Cannot create dir "%s".', $dir));
        }

        $extension = \strtolower(\pathinfo($path, PATHINFO_EXTENSION));
        $this->writer = match ($extension) {
            'csv' => new CSVWriter($this->options),
            'xlsx' => new XLSXWriter($this->options),
            'ods' => new ODSWriter($this->options),
            default => throw new UnsupportedTypeException('No writers supporting the given type: ' . $extension),
        };
        $this->writer->openToFile($path);

        if ($this->writer instanceof AbstractWriterMultiSheets) {
            if ($this->defaultSheet !== null) {
                $this->writer->getCurrentSheet()->setName($this->defaultSheet);
            } else {
                $this->defaultSheet = $this->writer->getCurrentSheet()->getName();
            }
        }
    }

    public function write(iterable $items): void
    {
        $writer = $this->writer;
        if ($writer === null) {
            throw BadMethodCallException::itemComponentNotInitialized($this);
        }

        if (!$this->headersAdded) {
            $this->headersAdded = true;
            if ($this->headers !== null) {
                $writer->addRow(Row::fromValues($this->headers));
            }
        }

        foreach ($items as $row) {
            if ($row instanceof WriteToSheetItem) {
                $this->changeSheet($row->getSheet());
                $row = $row->getItem();
            } elseif ($this->defaultSheet !== null) {
                $this->changeSheet($this->defaultSheet);
            }
            if (\is_array($row)) {
                $row = Row::fromValues($row);
            }
            if (!$row instanceof Row) {
                throw UnexpectedValueException::type('array|' . Row::class, $row);
            }

            $writer->addRow($row);
        }
    }

    public function flush(): void
    {
        if ($this->writer === null) {
            throw BadMethodCallException::itemComponentNotInitialized($this);
        }

        $this->writer->close();
        $this->writer = null;
        $this->headersAdded = false;
    }

    private function changeSheet(string $name): void
    {
        if (!$this->writer instanceof AbstractWriterMultiSheets) {
            return;
        }

        foreach ($this->writer->getSheets() as $sheet) {
            if ($sheet->getName() === $name) {
                $this->writer->setCurrentSheet($sheet);
                return;
            }
        }

        $sheet = $this->writer->addNewSheetAndMakeItCurrent();
        $sheet->setName($name);
    }
}
