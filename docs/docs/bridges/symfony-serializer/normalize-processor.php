<?php

declare(strict_types=1);

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Yokai\Batch\Bridge\Symfony\Serializer\NormalizeItemProcessor;

/** @var DenormalizerInterface $serializer */

new NormalizeItemProcessor(
    normalizer: $serializer,
    format: 'json', // or any format supported by the serializer
    context: [], // some extra context that will be provided during normalization
);
