<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\App\Entity\RickAndMorty;

enum LocationType: string
{
    case Planet = 'Planet';
    case Cluster = 'Cluster';
    case Spacestation = 'Space station';
    case Microverse = 'Microverse';
    case TV = 'TV';
    case Resort = 'Resort';
    case Fantasytown = 'Fantasy town';
    case Dream = 'Dream';
    case Dimension = 'Dimension';
    case Menagerie = 'Menagerie';
    case Game = 'Game';
    case Customs = 'Customs';
    case Daycare = 'Daycare';
    case Dwarfplanet = 'Dwarf planet (Celestial Dwarf)';
    case Miniverse = 'Miniverse';
    case Teenyverse = 'Teenyverse';
    case Box = 'Box';
    case Spacecraft = 'Spacecraft';
    case ArtificiallyGeneratedWorld = 'Artificially generated world';
    case Machine = 'Machine';
    case Arcade = 'Arcade';
    case Spa = 'Spa';
    case Quadrant = 'Quadrant';
    case Quasar = 'Quasar';
    case Mount = 'Mount';
    case Liquid = 'Liquid';
    case Convention = 'Convention';
    case Woods = 'Woods';
    case Diegesis = 'Diegesis';
    case NonDiegeticAlternativeReality = 'Non-Diegetic Alternative Reality';
    case Nightmare = 'Nightmare';
    case Asteroid = 'Asteroid';
    case AcidPlant = 'Acid Plant';
    case Reality = 'Reality';
    case DeathStar = 'Death Star';
    case Base = 'Base';
    case ElementalRings = 'Elemental Rings';
    case Human = 'Human';
    case Space = 'Space';
    case Hell = 'Hell';
    case PoliceDepartment = 'Police Department';
    case Country = 'Country';
    case Consciousness = 'Consciousness';
    case Memory = 'Memory';
    case Unknown = 'unknown';
}
