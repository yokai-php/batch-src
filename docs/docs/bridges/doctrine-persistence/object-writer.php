<?php

declare(strict_types=1);

use Doctrine\Persistence\ManagerRegistry;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectWriter;

/** @var ManagerRegistry $managerRegistry */

new ObjectWriter(
    doctrine: $managerRegistry,
);
