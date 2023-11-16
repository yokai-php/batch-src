<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\UserInterface\Templating;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\ConfigurableTemplating;

final class ConfigurableTemplatingTest extends TestCase
{
    public function test(): void
    {
        $twig = new Environment(
            new ArrayLoader([
                '@YokaiBatch/prefix/main.html.twig' => '{{ _context|json_encode|raw }}',
            ]),
        );
        $templating = new ConfigurableTemplating('@YokaiBatch/prefix', ['bar' => 'bar']);

        self::assertSame(
            '{"bar":"bar","foo":"foo"}',
            $twig->render($templating->name('main.html.twig'), $templating->context(['foo' => 'foo'])),
        );
    }
}
