<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty;

enum CharacterSpecie: string
{
    case Human = 'Human';
    case Alien = 'Alien';
    case Humanoid = 'Humanoid';
    case Poopybutthole = 'Poopybutthole';
    case MythologicalCreature = 'Mythological Creature';
    case Animal = 'Animal';
    case Robot = 'Robot';
    case Cronenberg = 'Cronenberg';
    case Disease = 'Disease';
    case Unknown = 'unknown';
}
