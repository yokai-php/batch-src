<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty;

enum CharacterGender: string
{
    case Male = 'Male';
    case Female = 'Female';
    case Unknown = 'unknown';
}
