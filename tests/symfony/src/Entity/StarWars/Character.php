<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars;

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
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: 'string', unique: true)]
    #[Assert\NotNull]
    public null|string $name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    public null|int $birthYear = null;

    #[ORM\Column(type: 'string')]
    #[Assert\NotNull]
    public null|string $gender = null;

    #[ORM\ManyToOne(targetEntity: Planet::class)]
    public null|Planet $homeWorld = null;

    #[ORM\ManyToOne(targetEntity: Specie::class)]
    public null|Specie $specie = null;
}
