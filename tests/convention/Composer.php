<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention;

final class Composer
{
    private array $json;

    public function __construct(string $path)
    {
        $this->json = \json_decode(\file_get_contents($path), true);
    }

    public function name(): string
    {
        return $this->json['name'];
    }

    /**
     * @return array<string, string>
     */
    public function replace(): array
    {
        return $this->json['replace'] ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function require(): array
    {
        return $this->json['require'] ?? [];
    }

    /**
     * @return array<string>
     */
    public function suggest(): array
    {
        return \array_keys($this->json['suggest'] ?? []);
    }

    /**
     * @return array<string>
     */
    public function packages(): array
    {
        return \array_keys($this->require());
    }

    /**
     * @return array<string, string>
     */
    public function autoload(): array
    {
        return $this->json['autoload']['psr-4'] ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function requireDev(): array
    {
        return $this->json['require-dev'] ?? [];
    }

    /**
     * @return array<string>
     */
    public function packagesDev(): array
    {
        return \array_keys($this->requireDev());
    }

    /**
     * @return array<string, string>
     */
    public function autoloadDev(): array
    {
        return $this->json['autoload-dev']['psr-4'] ?? [];
    }
}
