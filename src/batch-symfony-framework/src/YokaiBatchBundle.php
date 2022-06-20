<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\CompilerPass\RegisterJobsCompilerPass;

/**
 * yokai/batch Symfony Bundle.
 */
final class YokaiBatchBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterJobsCompilerPass());
    }
}
