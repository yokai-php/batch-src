<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Doctrine\ORM;

class User
{
    public string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}
