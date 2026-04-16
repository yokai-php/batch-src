<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\DependencyInjection;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection\Dsn;

final class DsnTest extends TestCase
{
    #[DataProvider('parse')]
    public function testParse(
        string $dsn,
        string $scheme,
        string $host,
        string $path,
        string|null $optionKey,
        string|null $optionDefault,
        string|null $optionValue,
    ): void {
        $parsed = Dsn::parse($dsn);

        self::assertSame($scheme, $parsed->getScheme());
        self::assertSame($host, $parsed->getHost());
        self::assertSame($path, $parsed->getPath());
        if ($optionKey !== null) {
            self::assertSame($optionValue, $parsed->getOption($optionKey, $optionDefault));
        }
    }

    public static function parse(): \Generator
    {
        yield 'simple scheme only' => [
            'simple://simple',
            'simple',
            'simple',
            '',
            // option to check : none
            null,
            null,
            null,
        ];
        yield 'dbal with host' => [
            'dbal://default',
            'dbal',
            'default',
            '',
            // option to check : none
            null,
            null,
            null,
        ];
        yield 'dbal with query option' => [
            'dbal://default?table=my_table',
            'dbal',
            'default',
            '',
            // option to check
            'table',
            null,
            'my_table',
        ];
        yield 'filesystem with absolute path' => [
            'filesystem:///var/batch',
            'filesystem',
            '',
            '/var/batch',
            // option to check : none
            null,
            null,
            null,
        ];
        yield 'filesystem with Symfony parameter in host' => [
            'filesystem://%kernel.project_dir%/var/batch',
            'filesystem',
            '%kernel.project_dir%',
            '/var/batch',
            // option to check : none
            null,
            null,
            null,
        ];
        yield 'filesystem with Symfony parameter and serializer option' => [
            'filesystem://%kernel.project_dir%/var/batch?serializer=Acme\\Serializer',
            'filesystem',
            '%kernel.project_dir%',
            '/var/batch',
            // option to check
            'serializer',
            null,
            'Acme\\Serializer',
        ];
        yield 'service with id option' => [
            'service://service?id=app.my_storage',
            'service',
            'service',
            '',
            'id',
            null,
            'app.my_storage',
        ];
        yield 'option missing returns default' => [
            'dbal://default',
            'dbal',
            'default',
            '',
            // option to check
            'table',
            'fallback',
            'fallback',
        ];
        yield 'option missing returns null when no default' => [
            'dbal://default',
            'dbal',
            'default',
            '',
            // option to check
            'table',
            null,
            null,
        ];
        yield 'no scheme delimiter returns empty object' => [
            'not-a-dsn',
            '',
            '',
            '',
            // option to check
            null,
            null,
            null,
        ];
    }

    #[DataProvider('isValid')]
    public function testIsValid(string $dsn, bool $expected): void
    {
        self::assertSame($expected, Dsn::isValid($dsn));
    }

    public static function isValid(): \Generator
    {
        yield 'simple DSN' => ['simple://simple', true];
        yield 'dbal DSN' => ['dbal://default', true];
        yield 'filesystem with path' => ['filesystem:///var/batch', true];
        yield 'filesystem with Symfony parameter' => ['filesystem://%kernel.project_dir%/batch', true];
        yield 'bare Symfony parameter placeholder' => ['%env(BATCH_STORAGE_DSN)%', true];
        yield 'bare named parameter placeholder' => ['%my_dsn_param%', true];
        yield 'plain string without scheme' => ['not-a-dsn', false];
        yield 'empty string' => ['', false];
        yield 'scheme without authority separator' => ['filesystem:something', false];
    }
}
