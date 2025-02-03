<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty\ImportRickAndMortyCharacterJob;

/**
 * A character from Rick and Morty universe.
 * Imported via {@see ImportRickAndMortyCharacterJob}.
 */
#[ORM\Entity]
#[ORM\Table(name: 'rick_and_morty_character')]
#[UniqueEntity('name')]
class Character
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: 'string')]
    #[Assert\NotNull]
    public null|string $name;

    #[ORM\Column(type: 'string', enumType: CharacterStatus::class)]
    #[Assert\NotNull]
    public null|CharacterStatus $status;

    #[ORM\Column(type: 'string', enumType: CharacterSpecie::class)]
    #[Assert\NotNull]
    public null|CharacterSpecie $specie;

    #[ORM\Column(type: 'string', enumType: CharacterGender::class)]
    #[Assert\NotNull]
    public null|CharacterGender $gender;

    #[ORM\Column(type: 'string', nullable: true)]
    public null|string $description;

    #[ORM\ManyToOne(targetEntity: Location::class)]
    public null|Location $origin;

    #[ORM\ManyToOne(targetEntity: Location::class)]
    public null|Location $location;

    /**
     * @var Collection<int, Episode>
     */
    #[ORM\ManyToMany(targetEntity: Episode::class)]
    #[ORM\JoinTable(name: 'rick_and_morty_character_episode')]
    public Collection $episodes;

    public function __construct()
    {
        $this->episodes = new ArrayCollection();
    }
}
