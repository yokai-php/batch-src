<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Reader\CSV\Reader as CsvReader;
use Box\Spout\Reader\SheetInterface;
use Yokai\Batch\Exception\InvalidArgumentException;
use Yokai\Batch\Exception\UndefinedJobParameterException;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Item\ItemReaderInterface;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;
use Yokai\Batch\Warning;

final class FlatFileReader implements
    ItemReaderInterface,
    JobExecutionAwareInterface
{
    use JobExecutionAwareTrait;

    public const SOURCE_FILE_PARAMETER = 'sourceFile';

    public const HEADERS_MODE_SKIP = 'skip';
    public const HEADERS_MODE_COMBINE = 'combine';
    public const HEADERS_MODE_NONE = 'none';
    public const AVAILABLE_HEADERS_MODES = [
        self::HEADERS_MODE_SKIP,
        self::HEADERS_MODE_COMBINE,
        self::HEADERS_MODE_NONE,
    ];

    /**
     * @var string
     */
    private string $type;

    /**
     * @var array
     */
    private array $options;

    /**
     * @var string
     */
    private string $headersMode;

    /**
     * @var array|null
     */
    private ?array $headers;

    /**
     * @var string|null
     */
    private ?string $filePath;

    public function __construct(
        string $type,
        array $options = [],
        string $headersMode = self::HEADERS_MODE_NONE,
        array $headers = null,
        string $filePath = null
    ) {
        if (!in_array($headersMode, self::AVAILABLE_HEADERS_MODES, true)) {
            throw UnexpectedValueException::enum(self::AVAILABLE_HEADERS_MODES, $headersMode, 'Invalid header mode.');
        }
        if ($headers !== null && $headersMode === self::HEADERS_MODE_COMBINE) {
            throw new InvalidArgumentException(
                sprintf('In "%s" header mode you should not provide header by yourself', self::HEADERS_MODE_COMBINE)
            );
        }

        $this->type = $type;
        $this->options = $options;
        $this->headersMode = $headersMode;
        $this->headers = $headers;
        $this->filePath = $filePath;
    }

    /**
     * @inheritDoc
     */
    public function read(): iterable
    {
        $reader = ReaderFactory::createFromType($this->type);
        if ($reader instanceof CsvReader) {
            if (isset($this->options['delimiter'])) {
                $reader->setFieldDelimiter($this->options['delimiter']);
            }
            if (isset($this->options['enclosure'])) {
                $reader->setFieldEnclosure($this->options['enclosure']);
            }
            if (isset($this->options['encoding'])) {
                $reader->setEncoding($this->options['encoding']);
            }
        }
        $reader->open($this->getFilePath());

        $headers = $this->headers;

        /** @var SheetInterface $sheet */
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                if ($row instanceof Row) {
                    $row = $row->toArray();
                }

                if ($rowIndex === 1) {
                    if ($this->headersMode === self::HEADERS_MODE_COMBINE) {
                        $headers = $row;
                    }
                    if (in_array($this->headersMode, [self::HEADERS_MODE_COMBINE, self::HEADERS_MODE_SKIP])) {
                        continue;
                    }
                }

                if (is_array($headers)) {
                    try {
                        /** @var array<string, mixed>|false $combined */
                        $combined = @array_combine($headers, $row);
                        if ($combined === false) {
                            // @codeCoverageIgnoreStart
                            // Prior to PHP 8.0 array_combine only trigger a warning
                            // Now it is throwing a ValueError
                            throw new \ValueError(
                                'array_combine(): Argument #1 ($keys) and argument #2 ($values) ' .
                                'must have the same number of elements'
                            );
                            // @codeCoverageIgnoreEnd
                        }
                    } catch (\ValueError $exception) {
                        $this->jobExecution->addWarning(
                            new Warning(
                                'Expecting row {row} to have exactly {expected} columns(s), but got {actual}.',
                                [
                                    '{row}' => (string)$rowIndex,
                                    '{expected}' => (string)count($headers),
                                    '{actual}' => (string)count($row),
                                ],
                                ['headers' => $headers, 'row' => $row]
                            )
                        );
                        continue;
                    }

                    $row = $combined;
                }

                yield $row;
            }
        }

        $reader->close();
    }

    protected function getFilePath(): string
    {
        if ($this->filePath) {
            return $this->filePath;
        }

        try {
            return (string)$this->jobExecution->getParameter(self::SOURCE_FILE_PARAMETER);
        } catch (UndefinedJobParameterException $exception) {
            return (string)$this->jobExecution->getRootExecution()->getParameter(self::SOURCE_FILE_PARAMETER);
        }
    }
}
