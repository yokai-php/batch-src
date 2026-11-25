<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Repository
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\Column(type: Types::STRING)]
    public string|null $label = null;

    #[ORM\Column(type: Types::STRING)]
    public string|null $url = null;
}
