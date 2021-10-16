<?php

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects

use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__ . '/../vendor/autoload.php';

$artifactDir = @getenv('ARTIFACT_DIR');
if (false === $artifactDir) {
    throw new \LogicException('Missing "ARTIFACT_DIR" env var.');
}

$filesystem = new Filesystem();
if (is_dir($artifactDir)) {
    $filesystem->chmod($artifactDir, 0777, 0000, true);
}
$filesystem->remove(__DIR__ . '/symfony/var');
$filesystem->remove($artifactDir);
$filesystem->mkdir($artifactDir);

define('ARTIFACT_DIR', $artifactDir);
