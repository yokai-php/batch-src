<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Developer
{
    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public $id;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string')]
    public $firstName;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string')]
    public $lastName;

    /**
     * @var Collection
     */
    #[ORM\ManyToMany(targetEntity: Badge::class)]
    public $badges;

    /**
     * @var Collection
     */
    #[ORM\ManyToMany(targetEntity: Repository::class)]
    public $repositories;

    public function __construct()
    {
        $this->badges = new ArrayCollection();
        $this->repositories = new ArrayCollection();
    }
}
