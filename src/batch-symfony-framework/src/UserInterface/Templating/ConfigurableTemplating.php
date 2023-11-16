<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating;

/**
 * Apply a prefix to every template names.
 */
final class ConfigurableTemplating implements TemplatingInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private string $prefix,
        private array $context,
    ) {
    }

    public function name(string $name): string
    {
        return \rtrim($this->prefix, '/') . '/' . \ltrim($name, '/');
    }

    public function context(array $context): array
    {
        return \array_merge($this->context, $context);
    }
}
