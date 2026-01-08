<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Developer
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public null|int $id = null;

    #[ORM\Column(type: Types::STRING)]
    public null|string $firstName = null;

    #[ORM\Column(type: Types::STRING)]
    public null|string $lastName = null;

    /**
     * @var Collection<int, Badge>
     */
    #[ORM\ManyToMany(targetEntity: Badge::class)]
    public Collection $badges;

    /**
     * @var Collection<int, Repository>
     */
    #[ORM\ManyToMany(targetEntity: Repository::class)]
    public Collection $repositories;

    public function __construct()
    {
        $this->badges = new ArrayCollection();
        $this->repositories = new ArrayCollection();
    }
}
