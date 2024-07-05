<?php

namespace App\Command;

use App\Entity\Family;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'app:import')]
final class ImportCommand extends Command
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly ManagerRegistry $doctrine,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = __DIR__ . '/families.jsonl';
        $file = @\fopen($path, 'r');
        if ($file === false) {
            throw new \RuntimeException(\sprintf('Cannot open %s for reading.', $path));
        }

        $manager = $this->doctrine->getManagerForClass(Family::class);
        \assert($manager !== null);

        $families = [];
        while ($line = \fgets($file)) {
            try {
                $data = \json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                \assert(\is_array($data));
            } catch (\JsonException) {
                continue;
            }

            $family = Family::fromData($data);
            $violations = $this->validator->validate($family);
            if (\count($violations) > 0) {
                continue;
            }

            $families[] = $family;

            if (\count($families) % 500 === 0) {
                foreach ($families as $family) {
                    $manager->persist($family);
                }
                $manager->flush($family);
            }
        }

        \fclose($file);

        if (\count($families) > 0) {
            foreach ($families as $family) {
                $manager->persist($family);
            }
            $manager->flush();
        }

        return self::SUCCESS;
    }
}
