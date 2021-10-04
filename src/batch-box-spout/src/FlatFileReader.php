<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Reader\CSV\Reader as CsvReader;
use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\SheetInterface;
use Generator;
use Yokai\Batch\Exception\InvalidArgumentException;
use Yokai\Batch\Exception\UnexpectedValueException;
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

    public const HEADERS_MODE_SKIP = 'skip';
    public const HEADERS_MODE_COMBINE = 'combine';
    public const HEADERS_MODE_NONE = 'none';

    private const HEADERS_MODES = [
        self::HEADERS_MODE_SKIP,
        self::HEADERS_MODE_COMBINE,
        self::HEADERS_MODE_NONE,
    ];

    private const TYPES = [Type::CSV, Type::XLSX, Type::ODS];

    private string $type;

    /**
     * @phpstan-var array{delimiter?: string, enclosure?: string}
     */
    private array $options;

    private string $headersMode;

    /**
     * @phpstan-var list<string>|null
     */
    private ?array $headers;

    private JobParameterAccessorInterface $filePath;

    /**
     * @phpstan-param array{delimiter?: string, enclosure?: string} $options
     * @phpstan-param list<string>|null                             $headers
     */
    public function __construct(
        string $type,
        JobParameterAccessorInterface $filePath,
        array $options = [],
        string $headersMode = self::HEADERS_MODE_NONE,
        array $headers = null
    ) {
        if (!in_array($type, self::TYPES, true)) {
            throw UnexpectedValueException::enum(self::TYPES, $type, 'Invalid type.');
        }
        if (!in_array($headersMode, self::HEADERS_MODES, true)) {
            throw UnexpectedValueException::enum(self::HEADERS_MODES, $headersMode, 'Invalid header mode.');
        }
        if ($headers !== null && $headersMode === self::HEADERS_MODE_COMBINE) {
            throw new InvalidArgumentException(
                sprintf('In "%s" header mode you should not provide header by yourself', self::HEADERS_MODE_COMBINE)
            );
        }

        $this->type = $type;
        $this->filePath = $filePath;
        $this->options = $options;
        $this->headersMode = $headersMode;
        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    public function read(): iterable
    {
        $reader = ReaderFactory::createFromType($this->type);
        if ($reader instanceof CsvReader) {
            $reader->setFieldDelimiter($this->options['delimiter'] ?? ',');
            $reader->setFieldEnclosure($this->options['enclosure'] ?? '"');
            if (isset($this->options['encoding'])) {
                $reader->setEncoding($this->options['encoding']);
            }
        }
        $reader->open((string)$this->filePath->get($this->jobExecution));

        $headers = $this->headers;

        foreach ($this->rows($reader) as $rowIndex => $row) {
            if ($rowIndex === 1) {
                if ($this->headersMode === self::HEADERS_MODE_COMBINE) {
                    $headers = $row;
                }
                if (in_array($this->headersMode, [self::HEADERS_MODE_COMBINE, self::HEADERS_MODE_SKIP])) {
                    continue;
                }
            }

            if (is_array($headers)) {
                $row = $this->combine($headers, $row, $rowIndex);
                if ($row === null) {
                    continue;
                }
            }

            yield $row;
        }

        $reader->close();
    }

    /**
     * @phpstan-return Generator<int, array>
     */
    private function rows(ReaderInterface $reader): Generator
    {
        /** @var SheetInterface $sheet */
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                if ($row instanceof Row) {
                    $row = $row->toArray();
                }

                yield $rowIndex => $row;
            }
        }
    }

    /**
     * @phpstan-param array<int, string> $headers
     * @phpstan-param array<int, string> $row
     *
     * @phpstan-return array<string, string>|null
     */
    private function combine(array $headers, array $row, int $rowIndex): ?array
    {
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

            return $combined;
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
        }

        return null;
    }
}
