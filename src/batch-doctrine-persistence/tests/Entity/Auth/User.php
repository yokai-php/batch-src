<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\Persistence\Entity\Auth;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth_user')]
class User
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\Column(type: Types::STRING)]
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
