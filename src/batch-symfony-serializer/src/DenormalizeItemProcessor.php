<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Serializer;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Yokai\Batch\Job\Item\Exception\SkipItemException;
use Yokai\Batch\Job\Item\ItemProcessorInterface;

/**
 * This {@see ItemProcessorInterface} uses Symfony's serializer to denormalize items.
 */
final class DenormalizeItemProcessor implements ItemProcessorInterface
{
    private DenormalizerInterface $denormalizer;
    private string $type;
    private ?string $format;

    /**
     * @phpstan-var array<string, mixed>
     */
    private array $context;

    /**
     * @phpstan-param array<string, mixed> $context
     */
    public function __construct(
        DenormalizerInterface $denormalizer,
        string $type,
        string $format = null,
        array $context = []
    ) {
        $this->denormalizer = $denormalizer;
        $this->type = $type;
        $this->format = $format;
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function process($item)
    {
        try {
            if (!$this->denormalizer->supportsDenormalization($item, $this->type, $this->format)) {
                throw new UnsupportedException('Unable to denormalize item. Not supported.');
            }

            $object = $this->denormalizer->denormalize($item, $this->type, $this->format, $this->context);
        } catch (ExceptionInterface $exception) {
            throw SkipItemException::onError(
                $item,
                $exception,
                ['format' => $this->format, 'context' => $this->context]
            );
        }

        return $object;
    }
}
