<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\RickAndMorty\ImportRickAndMortyEpisodeJob;

/**
 * An episode of Rick and Morty.
 * Imported via {@see ImportRickAndMortyEpisodeJob}.
 */
#[ORM\Entity]
#[ORM\Table(name: 'rick_and_morty_episode')]
#[UniqueEntity('code')]
class Episode
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: Types::STRING, unique: true)]
    #[Assert\NotNull]
    public null|string $code = null;

    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotNull]
    public null|string $name = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    public null|\DateTimeImmutable $date = null;
}
