<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterFactory;
use Box\Spout\Writer\CSV\Writer as CsvWriter;
use Box\Spout\Writer\WriterInterface;
use Yokai\Batch\Exception\BadMethodCallException;
use Yokai\Batch\Exception\RuntimeException;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Item\FlushableInterface;
use Yokai\Batch\Job\Item\InitializableInterface;
use Yokai\Batch\Job\Item\ItemWriterInterface;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;

final class FlatFileWriter implements
    ItemWriterInterface,
    JobExecutionAwareInterface,
    InitializableInterface,
    FlushableInterface
{
    use JobExecutionAwareTrait;

    public const OUTPUT_FILE_PARAMETER = 'outputFile';

    /**
     * @var string
     */
    private string $type;

    /**
     * @var array|null
     */
    private ?array $headers;

    /**
     * @var WriterInterface|null
     */
    private ?WriterInterface $writer = null;

    /**
     * @var bool
     */
    private bool $headersAdded = false;

    /**
     * @var string|null
     */
    private ?string $filePath;

    /**
     * @var array
     */
    private array $options;

    public function __construct(string $type, array $headers = null, string $filePath = null, array $options = [])
    {
        $this->type = $type;
        $this->headers = $headers;
        $this->filePath = $filePath;
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $path = $this->getFilePath();
        $dir = dirname($path);
        if (!@is_dir($dir) && !@mkdir($dir, 0777, true)) {
            throw new RuntimeException(
                \sprintf('Cannot create dir "%s".', $dir)
            );
        }

        $this->writer = WriterFactory::createFromType($this->type);
        if ($this->writer instanceof CsvWriter) {
            $this->writer->setFieldDelimiter($this->options['delimiter'] ?? ',');
            $this->writer->setFieldEnclosure($this->options['enclosure'] ?? '"');
        }
        $this->writer->openToFile($path);
    }

    /**
     * @inheritDoc
     */
    public function write(iterable $items): void
    {
        if ($this->writer === null) {
            throw BadMethodCallException::itemComponentNotInitialized($this);
        }

        if (!$this->headersAdded) {
            $this->headersAdded = true;
            if ($this->headers !== null) {
                $this->writer->addRow(WriterEntityFactory::createRowFromArray($this->headers));
            }
        }

        foreach ($items as $row) {
            if (!is_array($row)) {
                throw UnexpectedValueException::type('array', $row);
            }
            $this->writer->addRow(WriterEntityFactory::createRowFromArray($row));
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

    protected function getFilePath(): string
    {
        return $this->filePath ?: (string)$this->jobExecution->getParameter(self::OUTPUT_FILE_PARAMETER);
    }
}
