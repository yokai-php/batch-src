<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\DependencyInjection;

/**
 * Value object representing a parsed DSN string, with helpers for validation and option access.
 *
 * Unlike {@see parse_url}, this class uses manual string parsing so that Symfony container
 * parameter placeholders in the path (e.g. "%kernel.project_dir%") are preserved verbatim
 * and passed as-is to DI definitions.
 */
final readonly class Dsn
{
    private function __construct(
        private string $scheme,
        private string $host,
        private string $path,
        /**
         * @var array<string, string>
         */
        private array $options,
    ) {
    }

    /**
     * Parse a DSN string into a value object.
     *
     * Supports DSN strings containing Symfony container parameter placeholders in the
     * authority/path portion (e.g. "filesystem://%kernel.project_dir%/var/batch").
     */
    public static function parse(string $dsn): self
    {
        $schemeEnd = \strpos($dsn, '://');
        if ($schemeEnd === false) {
            return new self('', '', '', []);
        }

        $scheme = \substr($dsn, 0, $schemeEnd);
        $rest = \substr($dsn, $schemeEnd + 3);

        $queryStart = \strpos($rest, '?');
        if ($queryStart !== false) {
            $authorityPath = \substr($rest, 0, $queryStart);
            \parse_str(\substr($rest, $queryStart + 1), $options);
        } else {
            $authorityPath = $rest;
            $options = [];
        }
        /** @var array<string, string> $options */

        $slashPos = \strpos($authorityPath, '/');
        if ($slashPos !== false) {
            $host = \substr($authorityPath, 0, $slashPos);
            $path = \substr($authorityPath, $slashPos);
        } else {
            $host = $authorityPath;
            $path = '';
        }

        return new self($scheme, $host, $path, $options);
    }

    /**
     * Check whether a DSN string is valid (has a scheme followed by "://").
     *
     * Pure Symfony parameter placeholders (e.g. "%env(BATCH_DSN)") are accepted as-is,
     * since their value is resolved at runtime and cannot be validated at config-processing time.
     */
    public static function isValid(string $dsn): bool
    {
        // A bare Symfony parameter placeholder (%name% or %env(NAME)%) cannot be validated as
        // a DSN at config-processing time; accept it unconditionally.
        if (\preg_match('/^%[^%]+%$/', $dsn) === 1) {
            return true;
        }

        return \preg_match('/^[a-z][a-z0-9+\-.]*:\/\//i', $dsn) === 1;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get an option value with default when undefined.
     *
     * @return ($default is string ? string : string|null)
     */
    public function getOption(string $key, string|null $default = null): string|null
    {
        return $this->options[$key] ?? $default;
    }
}
