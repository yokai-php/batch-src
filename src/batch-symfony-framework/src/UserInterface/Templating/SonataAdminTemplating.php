<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating;

use Sonata\AdminBundle\Templating\TemplateRegistryInterface;

/**
 * Add all variables required by a SonataAdminBundle template to the context.
 */
final class SonataAdminTemplating implements TemplatingInterface
{
    public function __construct(
        private TemplateRegistryInterface $templates,
    ) {
    }

    public function name(string $name): string
    {
        return '@YokaiBatch/sonata/' . \ltrim($name, '/');
    }

    public function context(array $context): array
    {
        return \array_merge([
            'base_template' => $this->templates->getTemplate('layout'),
            'filter_template' => $this->templates->getTemplate('filter'),
        ], $context);
    }
}
