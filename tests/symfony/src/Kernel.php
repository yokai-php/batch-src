<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Yokai\Batch\Bridge\Symfony\Framework\YokaiBatchBundle;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new YokaiBatchBundle();
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir() . '/config/packages/');
        $loader->load($this->getProjectDir() . '/config/services.yaml');
    }
}
