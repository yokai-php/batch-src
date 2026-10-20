<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;

final class YourService
{
    public function __construct(
        #[Target('yokaiBatchLogger')]
        private LoggerInterface $yokaiBatchLogger,
    ) {
    }

    public function method()
    {
        $this->yokaiBatchLogger->error(...);
    }
}
