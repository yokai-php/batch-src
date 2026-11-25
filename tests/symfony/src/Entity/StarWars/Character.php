<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars\ImportStarWarsCharacterJob;

/**
 * A character from Star Wars universe.
 * Imported via {@see ImportStarWarsCharacterJob}.
 */
#[ORM\Entity]
#[ORM\Table(name: 'star_wars_character')]
#[UniqueEntity('name')]
class Character
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: Types::STRING, unique: true)]
    #[Assert\NotNull]
    public string|null $name = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public int|null $birthYear = null;

    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotNull]
    public string|null $gender = null;

    #[ORM\ManyToOne(targetEntity: Planet::class)]
    public Planet|null $homeWorld = null;

    #[ORM\ManyToOne(targetEntity: Specie::class)]
    public Specie|null $specie = null;
}
