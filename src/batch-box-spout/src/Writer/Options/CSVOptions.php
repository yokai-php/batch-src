<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Writer\Options;

use Box\Spout\Writer\CSV\Writer as CSVWriter;
use Box\Spout\Writer\WriterInterface;
use Yokai\Batch\Exception\UnexpectedValueException;

/**
 * Options for writing CSV files.
 */
final class CSVOptions implements OptionsInterface
{
    private string $delimiter;
    private string $enclosure;
    private bool $addBOM;

    public function __construct(string $delimiter = ',', string $enclosure = '"', bool $addBOM = false)
    {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->addBOM = $addBOM;
    }

    /**
     * @inheritdoc
     */
    public function configure(WriterInterface $writer): void
    {
        if (!$writer instanceof CSVWriter) {
            throw UnexpectedValueException::type(CSVWriter::class, $writer);
        }

        $writer->setFieldDelimiter($this->delimiter);
        $writer->setFieldEnclosure($this->enclosure);
        $writer->setShouldAddBOM($this->addBOM);
    }
}
