<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'character')]
#[UniqueEntity('name')]
class Character
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: 'string', unique: true)]
    #[Assert\NotNull]
    public ?string $name;

    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $birthYear;

    #[ORM\Column(type: 'string')]
    #[Assert\NotNull]
    public ?string $gender;

    #[ORM\ManyToOne(targetEntity: Planet::class)]
    public ?Planet $homeWorld;

    #[ORM\ManyToOne(targetEntity: Specie::class)]
    public ?Specie $specie;
}
