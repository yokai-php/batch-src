<?php

declare(strict_types=1);

namespace Yokai\Batch\Job\Item\Reader;

use Generator;
use Yokai\Batch\Exception\RuntimeException;
use Yokai\Batch\Exception\UndefinedJobParameterException;
use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Item\ItemReaderInterface;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;

final class FixedColumnSizeFileReader implements
    ItemReaderInterface,
    JobExecutionAwareInterface
{
    use JobExecutionAwareTrait;

    public const SOURCE_FILE_PARAMETER = 'sourceFile';

    public const HEADERS_MODE_SKIP = 'skip';
    public const HEADERS_MODE_COMBINE = 'combine';
    public const HEADERS_MODE_NONE = 'none';
    private const AVAILABLE_HEADERS_MODES = [
        self::HEADERS_MODE_SKIP,
        self::HEADERS_MODE_COMBINE,
        self::HEADERS_MODE_NONE,
    ];

    /**
     * @var array
     */
    private array $columns;

    /**
     * @var string
     */
    private string $headersMode;

    /**
     * @var string|null
     */
    private ?string $filePath;

    public function __construct(
        array $columns,
        string $headersMode = self::HEADERS_MODE_NONE,
        string $filePath = null
    ) {
        if (!\in_array($headersMode, self::AVAILABLE_HEADERS_MODES, true)) {
            throw UnexpectedValueException::enum(self::AVAILABLE_HEADERS_MODES, $headersMode, 'Invalid header mode.');
        }

        $this->columns = $columns;
        $this->headersMode = $headersMode;
        $this->filePath = $filePath;
    }

    /**
     * @inheritdoc
     */
    public function read(): Generator
    {
        $handle = \fopen($path = $this->getFilePath(), 'r');
        if ($handle === false) {
            throw new RuntimeException(\sprintf('Cannot read %s.', $path));
        }

        $headers = \array_keys($this->columns);

        $index = -1;

        while (($line = \fgets($handle)) !== false) {
            $index++;

            $start = 0;
            $row = [];
            foreach ($this->columns as $size) {
                $row[] = \trim(\mb_substr($line, $start, $size));
                $start += $size;
            }

            if ($index === 0) {
                if ($this->headersMode === self::HEADERS_MODE_COMBINE) {
                    $headers = $row;
                }
                if (\in_array($this->headersMode, [self::HEADERS_MODE_COMBINE, self::HEADERS_MODE_SKIP], true)) {
                    continue;
                }
            }

            if (\is_array($headers)) {
                $row = \array_combine($headers, $row);
            }

            yield $row;
        }

        \fclose($handle);
    }

    private function getFilePath(): string
    {
        if ($this->filePath !== null) {
            return $this->filePath;
        }

        try {
            return (string)$this->jobExecution->getParameter(self::SOURCE_FILE_PARAMETER);
        } catch (UndefinedJobParameterException $exception) {
            return (string)$this->jobExecution->getRootExecution()->getParameter(self::SOURCE_FILE_PARAMETER);
        }
    }
}
