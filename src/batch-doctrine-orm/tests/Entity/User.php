<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\ORM\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\Column(type: 'string')]
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
