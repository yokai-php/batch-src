<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Box\Spout\Writer\Options;

use Box\Spout\Common\Entity\Style\Style;
use Box\Spout\Writer\ODS\Writer as ODSWriter;
use Box\Spout\Writer\WriterInterface;
use Yokai\Batch\Exception\UnexpectedValueException;

/**
 * Options for writing ODS files.
 */
final class ODSOptions implements OptionsInterface
{
    public function __construct(
        private ?string $sheet = null,
        private ?Style $style = null,
    ) {
    }

    public function configure(WriterInterface $writer): void
    {
        if (!$writer instanceof ODSWriter) {
            throw UnexpectedValueException::type(ODSWriter::class, $writer);
        }

        if ($this->sheet) {
            $writer->getCurrentSheet()->setName($this->sheet);
        }
        if ($this->style) {
            $writer->setDefaultRowStyle($this->style);
        }
    }
}
