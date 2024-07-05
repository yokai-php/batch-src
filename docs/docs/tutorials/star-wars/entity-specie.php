<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'specie')]
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
