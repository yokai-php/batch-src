<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Serializer\Dummy;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DummyNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private bool $supports,
        private mixed $value,
    ) {
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return $this->supports;
    }

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        return $this->value;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null)
    {
        return $this->supports;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        return $this->value;
    }
}
