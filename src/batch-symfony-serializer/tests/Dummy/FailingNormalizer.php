<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Serializer\Dummy;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class FailingNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private ExceptionInterface $exception,
    ) {
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return true;
    }

    public function normalize(
        mixed $object,
        string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {
        throw $this->exception;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return true;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        throw $this->exception;
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['*' => true];
    }
}
