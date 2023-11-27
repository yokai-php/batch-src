<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CliTest extends KernelTestCase
{
    public function testRegisteredCommands(): void
    {
        $names = \array_keys(
            (new Application(self::bootKernel()))->all('yokai'),
        );
        \sort($names);
        self::assertSame(
            [
                'yokai:batch:run',
                'yokai:batch:setup-storage',
            ],
            $names,
        );
    }
}
