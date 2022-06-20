<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention;

/**
 * Object representation of a Composer package (either yokai or vendor).
 */
final class Package
{
    /**
     * Package name (in composer)
     */
    public string $name;
    /**
     * Composer configuration object
     */
    public Composer $composer;

    public function __construct(
        /**
         * The absolute directory where the package lives
         */
        public string $path,
    ) {
        $this->composer = new Composer($path . '/composer.json');
        $this->name = $this->composer->name();
    }

    /**
     * Absolute dir where sources lives.
     */
    public function sources(): string
    {
        return \realpath($this->path . '/' . \array_values($this->composer->autoload())[0]);
    }

    /**
     * Namespace prefix of sources.
     */
    public function namespace(): string
    {
        return \array_keys($this->composer->autoload())[0];
    }

    /**
     * Absolute dir where tests lives.
     */
    public function tests(): string
    {
        return \realpath($this->path . '/' . \array_values($this->composer->autoloadDev())[0]);
    }

    /**
     * Namespace prefix of tests.
     */
    public function testsNamespace(): string
    {
        return \array_keys($this->composer->autoloadDev())[0];
    }
}
