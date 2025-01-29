<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty;

enum CharacterStatus: string
{
    case Alive = 'Alive';
    case Dead = 'Dead';
    case Unknown = 'unknown';
}
