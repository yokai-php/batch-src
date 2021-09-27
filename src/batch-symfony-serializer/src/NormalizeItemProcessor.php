<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Serializer;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Yokai\Batch\Job\Item\Exception\SkipItemException;
use Yokai\Batch\Job\Item\ItemProcessorInterface;

/**
 * This {@see ItemProcessorInterface} uses Symfony's serializer to normalize items.
 */
final class NormalizeItemProcessor implements ItemProcessorInterface
{
    private NormalizerInterface $normalizer;
    private ?string $format;

    /**
     * @phpstan-var array<string, mixed>
     */
    private array $context;

    /**
     * @phpstan-param array<string, mixed> $context
     */
    public function __construct(NormalizerInterface $normalizer, string $format = null, array $context = [])
    {
        $this->normalizer = $normalizer;
        $this->format = $format;
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function process($item)
    {
        try {
            if (!$this->normalizer->supportsNormalization($item, $this->format)) {
                throw new UnsupportedException('Unable to normalize item. Not supported.');
            }

            return $this->normalizer->normalize($item, $this->format, $this->context);
        } catch (ExceptionInterface $exception) {
            throw SkipItemException::onError(
                $item,
                $exception,
                ['format' => $this->format, 'context' => $this->context]
            );
        }
    }
}
