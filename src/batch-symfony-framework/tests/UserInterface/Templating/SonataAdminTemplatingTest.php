<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\UserInterface\Templating;

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Templating\TemplateRegistry;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\SonataAdminTemplating;

final class SonataAdminTemplatingTest extends TestCase
{
    public function test(): void
    {
        $twig = new Environment(
            new ArrayLoader([
                '@YokaiBatch/sonata/main.html.twig' => '{{ _context|json_encode|raw }}',
            ]),
        );
        $templating = new SonataAdminTemplating(
            new TemplateRegistry([
                'layout' => '@SonataAdmin/layout.html.twig',
                'filter' => '@SonataAdmin/filter.html.twig',
            ]),
        );

        self::assertSame(
            '{"base_template":"@SonataAdmin\/layout.html.twig","filter_template":"@SonataAdmin\/filter.html.twig","foo":"foo"}',
            $twig->render($templating->name('main.html.twig'), $templating->context(['foo' => 'foo'])),
        );
    }
}
