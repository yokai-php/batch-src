<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty\ImportRickAndMortyLocationJob;

/**
 * A location from Rick and Morty universe.
 * Imported via {@see ImportRickAndMortyLocationJob}.
 */
#[ORM\Entity]
#[ORM\Table(name: 'rick_and_morty_location')]
class Location
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: 'string')]
    #[Assert\NotNull]
    public null|string $name = null;

    #[ORM\Column(type: 'string', enumType: LocationType::class)]
    #[Assert\NotNull]
    public null|LocationType $type = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public null|string $dimension = null;
}
