<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Entity\StarWars;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars\ImportStarWarsPlanetJob;

/**
 * A planet from Star Wars universe.
 * Imported via {@see ImportStarWarsPlanetJob}.
 *
 * @ORM\Entity()
 * @ORM\Table(name="star_wars_planet")
 *
 * @UniqueEntity("name")
 */
class Planet
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
}
