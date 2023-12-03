<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Serializer\Dummy;

use ArrayObject;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DummyNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private bool $supports,
        private mixed $value,
    ) {
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = null): bool
    {
        return $this->supports;
    }

    public function normalize(mixed $object, string $format = null, array $context = []): ArrayObject|array|string|int|float|bool|null
    {
        return $this->value;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = null): bool
    {
        return $this->supports;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        return $this->value;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [];
    }
}
