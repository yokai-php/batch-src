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
 *
 * @ORM\Entity()
 * @ORM\Table(name="star_wars_character")
 *
 * @UniqueEntity("name")
 */
class Character
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public int $id;

    /**
     * @ORM\Column(unique=true)
     *
     * @Assert\NotNull()
     */
    public ?string $name;

    /**
     * @ORM\Column(type="integer")
     *
     * @Assert\NotNull()
     */
    public ?int $birthYear;

    /**
     * @ORM\Column()
     *
     * @Assert\NotNull()
     */
    public ?string $gender;

    /**
     * @ORM\ManyToOne(targetEntity=Planet::class)
     */
    public ?Planet $homeWorld;

    /**
     * @ORM\ManyToOne(targetEntity=Specie::class)
     */
    public ?Specie $specie;
}
