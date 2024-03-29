<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars;

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
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: 'string', unique: true)]
    #[Assert\NotNull]
    public ?string $name;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $classification;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $language;

    #[ORM\ManyToOne(targetEntity: Planet::class)]
    public Planet $homeWorld;
}
