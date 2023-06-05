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
    public function __construct(
        private NormalizerInterface $normalizer,
        private ?string $format = null,
        /**
         * @phpstan-var array<string, mixed>
         */
        private array $context = [],
    ) {
    }

    public function process(mixed $item): mixed
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
