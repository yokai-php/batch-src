<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'planet')]
#[UniqueEntity('name')]
class Planet
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: 'string', unique: true)]
    #[Assert\NotNull]
    public ?string $name;

    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $rotationPeriod;

    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $orbitalPeriod;

    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $population;

    #[ORM\Column(type: 'json')]
    #[Assert\NotNull]
    public array $terrain;
}
