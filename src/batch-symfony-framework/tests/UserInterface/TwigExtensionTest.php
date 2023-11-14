<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\UserInterface;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\JobSecurity;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\TwigExtension;
use Yokai\Batch\JobExecution;

final class TwigExtensionTest extends TestCase
{
    /**
     * @dataProvider security
     */
    public function testSecurity(JobSecurity $security, bool $granted): void
    {
        $twig = new Environment(new ArrayLoader(['default' => <<<TWIG
{% if yokai_batch_grant_list() %}list {% endif %}
{% if yokai_batch_grant_view(execution) %}view {% endif %}
{% if yokai_batch_grant_traces(execution) %}traces {% endif %}
{% if yokai_batch_grant_logs(execution) %}logs{% endif %}
TWIG
        ]));
        $twig->addExtension(new TwigExtension($security));

        self::assertSame(
            $granted ? 'list view traces logs' : '',
            $twig->render('default', ['execution' => JobExecution::createRoot('export', '64f05ed2c172a')]),
        );
    }

    public static function security(): \Generator
    {
        foreach ([true, false] as $granted) {
            yield [
                new JobSecurity(
                    new class($granted) implements AuthorizationCheckerInterface {
                        public function __construct(private bool $granted)
                        {
                        }

                        public function isGranted(mixed $attribute, mixed $subject = null): bool
                        {
                            return $this->granted;
                        }
                    },
                    'ROLE_UNUSED',
                    'ROLE_UNUSED',
                    'ROLE_UNUSED',
                    'ROLE_UNUSED',
                ),
                $granted,
            ];
        }
    }
}
