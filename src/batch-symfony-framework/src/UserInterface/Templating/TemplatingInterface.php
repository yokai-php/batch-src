<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating;

use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Controller\JobController;

/**
 * Templating configuration for application customization.
 * Used by {@see JobController} as an intermediate to render its templates.
 */
interface TemplatingInterface
{
    /**
     * Build Twig template name.
     */
    public function name(string $name): string;

    /**
     * Build Twig template context.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function context(array $context): array;
}
