<?php

declare(strict_types=1);

use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
use Yokai\Batch\Bridge\League\Flysystem\Job\MoveFilesJob;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;

/** @var FilesystemReader $sourceFilesystem */
/** @var FilesystemWriter $destinationFilesystem */

new MoveFilesJob(
    location: new StaticValueParameterAccessor('users.jsonl'),
    source: $sourceFilesystem,
    destination: $destinationFilesystem,
    transformLocation: null, // a closure you can use to transform file name on destination filesystem
);
