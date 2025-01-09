<?php

declare(strict_types=1);

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Bridge\Symfony\Validator\SkipInvalidItemProcessor;

/** @var ValidatorInterface $validator */

new SkipInvalidItemProcessor(
    validator: $validator,
    contraints: null, // optionally the constraint(s) to validate against
    groups: null, // optionally the validation groups to validate
);
