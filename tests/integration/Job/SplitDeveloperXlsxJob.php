<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration\Job;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\SheetInterface;
use OpenSpout\Reader\XLSX\Reader;
use Yokai\Batch\Bridge\OpenSpout\Writer\FlatFileWriter;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\JobExecution;

final class SplitDeveloperXlsxJob implements JobInterface
{
    public function __construct(
        private string $inputFile,
        private string $outputBadgeFile,
        private string $outputRepositoryFile,
        private string $outputDeveloperFile
    ) {
    }

    public function execute(JobExecution $jobExecution): void
    {
        $badges = [];
        $repositories = [];
        $developers = [];

        $reader = new Reader();
        $reader->open($this->inputFile);
        $sheets = iterator_to_array($reader->getSheetIterator(), false);
        [$badgeSheet, $repositorySheet] = $sheets;

        foreach ($this->sheetToArray($badgeSheet) as $row) {
            [$firstName, $lastName, $badgeLabel, $badgeRank] = $row;

            $badgeData = ['label' => $badgeLabel, 'rank' => $badgeRank];
            $badgeKey = $badgeLabel;
            $developerData = ['firstName' => $firstName, 'lastName' => $lastName, 'badges' => [], 'repositories' => []];
            $developerKey = $firstName . '/' . $lastName;

            $badges[$badgeKey] = $badges[$badgeKey] ?? $badgeData;
            $developers[$developerKey] = $developers[$developerKey] ?? $developerData;
            $developers[$developerKey]['badges'][] = $badgeLabel;
        }

        foreach ($this->sheetToArray($repositorySheet) as $row) {
            [$firstName, $lastName, $repositoryLabel, $repositoryUrl] = $row;

            $repositoryData = ['label' => $repositoryLabel, 'url' => $repositoryUrl];
            $repositoryKey = $repositoryUrl;
            $developerData = ['firstName' => $firstName, 'lastName' => $lastName, 'badges' => [], 'repositories' => []];
            $developerKey = $firstName . '/' . $lastName;

            $repositories[$repositoryKey] = $repositories[$repositoryKey] ?? $repositoryData;
            $developers[$developerKey] = $developers[$developerKey] ?? $developerData;
            $developers[$developerKey]['repositories'][] = $repositoryUrl;
        }

        foreach ($developers as &$developer) {
            $developer['badges'] = implode('|', $developer['badges']);
            $developer['repositories'] = implode('|', $developer['repositories']);
        }

        $reader->close();

        $this->writeToCsv($this->outputBadgeFile, $badges, ['label', 'rank']);
        $this->writeToCsv($this->outputRepositoryFile, $repositories, ['label', 'url']);
        $this->writeToCsv($this->outputDeveloperFile, $developers, ['firstName', 'lastName', 'badges', 'repositories']);

        unset(
            $badges,
            $repositories,
            $developers
        );
    }

    private function writeToCsv(string $filename, array $data, array $headers): void
    {
        $writer = new FlatFileWriter(new StaticValueParameterAccessor($filename), null, null, $headers);
        $writer->setJobExecution(JobExecution::createRoot('fake', 'fake'));
        $writer->initialize();
        $writer->write($data);
        $writer->flush();
    }

    private function sheetToArray(SheetInterface $sheet): array
    {
        return array_map(
            function ($row): array {
                if ($row instanceof Row) {
                    return $row->toArray();
                }

                return $row;
            },
            iterator_to_array(new \LimitIterator($sheet->getRowIterator(), 1), false)
        );
    }
}
