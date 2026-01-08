<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars\ImportStarWarsPlanetJob;

/**
 * A planet from Star Wars universe.
 * Imported via {@see ImportStarWarsPlanetJob}.
 */
#[ORM\Entity]
#[ORM\Table(name: 'star_wars_planet')]
#[UniqueEntity('name')]
class Planet
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: Types::STRING, unique: true)]
    #[Assert\NotNull]
    public null|string $name = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public null|int $rotationPeriod = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public null|int $orbitalPeriod = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public null|int $population = null;

    #[ORM\Column(type: Types::JSON)]
    #[Assert\NotNull]
    public array $terrain;
}
