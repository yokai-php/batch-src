<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\Tests;

use Generator;
use PHPUnit\Framework\Assert;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\Country\CountryJob;

final class CountryJobSet
{
    private const COUNT = 250;
    private const COUNT_IN_CSV = self::COUNT + 1; // header
    private const DIR = ARTIFACT_DIR . '/symfony/country';

    public static function sets(): Generator
    {
        yield [
            CountryJob::getJobName(),
            static function (JobExecution $execution) {
                JobAssert::assertIsSuccessful($execution);
                JobAssert::assertItemJobStats($execution, 1250, 1250, 1250);

                // assert variable written in Summary
                $summary = $execution->getSummary()->get('countries');
                Assert::assertCount(self::COUNT, $summary);
                Assert::assertContains(
                    [
                        'iso2' => 'FR',
                        'iso3' => 'FRA',
                        'name' => 'France',
                        'continent' => 'EU',
                        'currency' => 'EUR',
                        'phone' => '33',
                    ],
                    $summary
                );
                Assert::assertContains(
                    [
                        'iso2' => 'GB',
                        'iso3' => 'GBR',
                        'name' => 'United Kingdom',
                        'continent' => 'EU',
                        'currency' => 'GBP',
                        'phone' => '44',
                    ],
                    $summary
                );

                // assert written CSV file
                Assert::assertFileExists(self::DIR . '/countries.csv');
                $csv = \array_filter(\array_map('trim', \file(self::DIR . '/countries.csv')));
                Assert::assertCount(self::COUNT_IN_CSV, $csv);
                Assert::assertContains(
                    'FR,FRA,France,EU,EUR,33',
                    $csv
                );
                Assert::assertContains(
                    'GB,GBR,"United Kingdom",EU,GBP,44',
                    $csv
                );

                // assert written JSONL file
                Assert::assertFileExists(self::DIR . '/countries.jsonl');
                $jsonl = \array_filter(\array_map('trim', \file(self::DIR . '/countries.jsonl')));
                Assert::assertCount(self::COUNT, $jsonl);
                Assert::assertContains(
                    '{"iso2":"FR","iso3":"FRA","name":"France","continent":"EU","currency":"EUR","phone":"33"}',
                    $jsonl
                );
                Assert::assertContains(
                    '{"iso2":"GB","iso3":"GBR","name":"United Kingdom","continent":"EU","currency":"GBP","phone":"44"}',
                    $jsonl
                );
            },
        ];
    }
}
