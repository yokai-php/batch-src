<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;

final class YourService
{
    public function __construct(
        private LoggerInterface $yokaiBatchLogger,
    ) {
    }

    public function method()
    {
        $this->yokaiBatchLogger->error(...);
    }
}
