<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration\Processor;

use Doctrine\ORM\EntityManager;
use Yokai\Batch\Job\Item\ItemProcessorInterface;
use Yokai\Batch\Sources\Tests\Integration\Entity\Badge;
use Yokai\Batch\Sources\Tests\Integration\Entity\Developer;
use Yokai\Batch\Sources\Tests\Integration\Entity\Repository;

final class DeveloperProcessor implements ItemProcessorInterface
{
    private $manager;

    public function __construct(EntityManager $manager)
    {
        $this->manager = $manager;
    }

    public function process(mixed $item): Developer
    {
        $badges = $this->manager->getRepository(Badge::class)
            ->findBy(['label' => str_getcsv($item['badges'], '|')])
        ;
        $repositories = $this->manager->getRepository(Repository::class)
            ->findBy(['label' => str_getcsv($item['repositories'], '|')])
        ;

        $developer = new Developer();
        $developer->firstName = $item['firstName'];
        $developer->lastName = $item['lastName'];
        foreach ($badges as $badge) {
            $developer->badges->add($badge);
        }
        foreach ($repositories as $repository) {
            $developer->repositories->add($repository);
        }

        return $developer;
    }
}
