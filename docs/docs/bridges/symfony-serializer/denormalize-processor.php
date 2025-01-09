<?php

declare(strict_types=1);

use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Yokai\Batch\Bridge\Symfony\Serializer\DenormalizeItemProcessor;

/** @var DenormalizerInterface $serializer */

new DenormalizeItemProcessor(
    denormalizer: $serializer,
    type: User::class, // the expected class to instantiate
    context: [], // some extra context that will be provided during denormalization
);
