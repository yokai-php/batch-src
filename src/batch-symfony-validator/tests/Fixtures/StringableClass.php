<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Validator\Fixtures;

final class StringableClass
{
    public function __toString(): string
    {
        return '__toString';
    }
}
