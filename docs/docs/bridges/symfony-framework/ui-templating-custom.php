<?php

declare(strict_types=1);

namespace App\Batch;

use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\TemplatingInterface;

final class AppTemplating implements TemplatingInterface
{
    public function name(string $name): string
    {
        return "another-$name"; // change $name if you want
    }

    public function context(array $context): array
    {
        return \array_merge($context, ['foo' => 'bar']); // add variables to $context if you want
    }
}
