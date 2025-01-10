<?php

declare(strict_types=1);

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Yokai\Batch\Bridge\Doctrine\ORM\EntityReader;

/** @var ManagerRegistry $managerRegistry */

new EntityReader(
    doctrine: $managerRegistry,
    class: User::class,
);
