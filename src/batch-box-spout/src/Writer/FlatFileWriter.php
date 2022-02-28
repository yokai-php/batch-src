<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Writer;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterFactory;
use Box\Spout\Writer\WriterInterface;
use Box\Spout\Writer\WriterMultiSheetsAbstract;
use Yokai\Batch\Bridge\Box\Spout\Writer\Options\OptionsInterface;
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

    private JobParameterAccessorInterface $filePath;
    private OptionsInterface $options;

    /**
     * @phpstan-var list<string>|null
     */
    private ?array $headers;
    private ?WriterInterface $writer = null;
    private bool $headersAdded = false;
    private ?string $defaultSheet = null;

    /**
     * @phpstan-param list<string>|null $headers
     */
    public function __construct(
        JobParameterAccessorInterface $filePath,
        OptionsInterface $options,
        array $headers = null
    ) {
        $this->filePath = $filePath;
        $this->options = $options;
        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        /** @var string $path */
        $path = $this->filePath->get($this->jobExecution);
        $dir = \dirname($path);
        if (!@\is_dir($dir) && !@\mkdir($dir, 0777, true)) {
            throw new RuntimeException(
                \sprintf('Cannot create dir "%s".', $dir)
            );
        }

        $this->writer = WriterFactory::createFromFile($path);
        $this->writer->openToFile($path);
        $this->options->configure($this->writer);

        if ($this->writer instanceof WriterMultiSheetsAbstract) {
            $this->defaultSheet = $this->writer->getCurrentSheet()->getName();
        }
    }

    /**
     * @inheritDoc
     */
    public function write(iterable $items): void
    {
        $writer = $this->writer;
        if ($writer === null) {
            throw BadMethodCallException::itemComponentNotInitialized($this);
        }

        if (!$this->headersAdded) {
            $this->headersAdded = true;
            if ($this->headers !== null) {
                $writer->addRow(WriterEntityFactory::createRowFromArray($this->headers));
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
                $row = WriterEntityFactory::createRowFromArray($row);
            }
            if (!$row instanceof Row) {
                throw UnexpectedValueException::type('array|' . Row::class, $row);
            }

            $writer->addRow($row);
        }
    }

    /**
     * @inheritDoc
     */
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
        if (!$this->writer instanceof WriterMultiSheetsAbstract) {
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
