<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars\ImportStarWarsSpecieJob;

/**
 * A specie from Star Wars universe.
 * Imported via {@see ImportStarWarsSpecieJob}.
 */
#[ORM\Entity]
#[ORM\Table(name: 'star_wars_specie')]
#[UniqueEntity('name')]
class Specie
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: Types::STRING, unique: true)]
    #[Assert\NotNull]
    public null|string $name = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public null|string $classification = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public null|string $language = null;

    #[ORM\ManyToOne(targetEntity: Planet::class)]
    public Planet $homeWorld;
}
