<?php

namespace App\Job\Import;

use App\Entity\Badge;
use App\Entity\Developer;
use App\Entity\Repository;
use Doctrine\Persistence\ManagerRegistry;
use Yokai\Batch\Job\Item\ItemProcessorInterface;

final class ImportDevelopersProcessor implements ItemProcessorInterface
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function process($item)
    {
        $badges = $this->doctrine->getRepository(Badge::class)
            ->findBy(['label' => str_getcsv($item['badges'], '|')]);
        $repositories = $this->doctrine->getRepository(Repository::class)
            ->findBy(['label' => str_getcsv($item['repositories'], '|')]);

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
