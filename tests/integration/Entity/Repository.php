<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Repository
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @ORM\Column()
     */
    public $label;

    /**
     * @var string
     * @ORM\Column()
     */
    public $url;
}
