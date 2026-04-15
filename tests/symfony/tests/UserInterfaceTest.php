<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Symfony\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Launcher\JobLauncherInterface;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\Country\CountryJob;
use Yokai\Batch\Sources\Tests\Symfony\App\Job\StarWars\ImportStarWarsJob;

final class UserInterfaceTest extends WebTestCase
{
    private const TRANSLATIONS = [
        'country' => 'Country import',
        'star-wars.import' => 'Star Wars import',
        'rick-and-morty.import' => 'Rick and Morty import',
    ];

    /**
     * @var array<string, JobExecution>
     */
    private static array $executions;

    public static function setUpBeforeClass(): void
    {
        (new Filesystem())->remove(__DIR__ . '/../var/batch/');

        $container = self::getContainer();
        /** @var JobLauncherInterface $launcher */
        $launcher = $container->get(JobLauncherInterface::class);
        /** @var array<mixed> $set */
        foreach (JobTest::configs() as $set) {
            /**
             * @var string $job
             * @var \Closure|null $setup
             * @var array<string, mixed> $config
             */
            [0 => $job, 2 => $setup, 3 => $config] = \array_replace([null, null, null, []], $set);

            if (isset(self::$executions[$job])) {
                continue;
            }

            if ($setup) {
                $setup($container);
            }

            self::$executions[$job] = $launcher->launch($job, $config);
        }
        self::ensureKernelShutdown();
    }

    public function testList(): void
    {
        $http = self::createClient();
        $page = $http->request('get', '/jobs');

        self::assertResponseIsSuccessful();
        self::assertCount(\count(self::$executions), $page->filter('.job-list > tbody > tr'));
        foreach (self::$executions as $execution) {
            self::assertSelectorTextContains(
                '.job-list > tbody > tr:contains("' . $execution->getId() . '") > td:nth-child(2)',
                self::TRANSLATIONS[$execution->getJobName()],
            );
            self::assertSelectorTextContains(
                '.job-list > tbody > tr:contains("' . $execution->getId() . '") > td:nth-child(3)',
                'Completed',
            );
        }
    }

    #[DataProvider('view')]
    public function testView(string $job, \Closure $expected): void
    {
        $execution = self::$executions[$job];

        $http = self::createClient();
        $page = $http->request('get', "/jobs/{$execution->getJobName()}/{$execution->getId()}");

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.job-show', "Execution ID {$execution->getId()}");
        $expected($page);
    }

    public static function view(): \Generator
    {
        yield [
            CountryJob::getJobName(),
            static function () {
                self::assertSelectorTextContains('.job-show', 'Job name Country import');
                self::assertSelectorTextContains('.job-show', 'Status Completed');
                self::assertSelectorTextContains('.job-show', 'Count: 250');
                if (\method_exists(self::class, 'assertSelectorCount')) {
                    self::assertSelectorCount(250, '.job-show img');
                }
                self::assertSelectorExists('.job-show img[src="https://flagcdn.com/48x36/fr.png"]');
            },
        ];
        yield [
            ImportStarWarsJob::getJobName(),
            static function (Crawler $page) {
                self::assertSelectorTextContains('.job-show', 'Job name Star Wars import');
                self::assertSelectorTextContains('.job-show', 'Status Completed');
                self::assertCount(3, $page->filter('#children > table > tbody > tr'));
                self::assertSelectorTextContains('#children > table > tbody', 'Star Wars planets import');
                self::assertSelectorTextContains('#children > table > tbody', 'Star Wars species import');
                self::assertSelectorTextContains('#children > table > tbody', 'Star Wars characters import');
            },
        ];
    }

    #[DataProvider('logs')]
    public function testLogs(string $job, \Closure $expected): void
    {
        $execution = self::$executions[$job];

        $http = self::createClient();
        $http->request('get', "/jobs/{$execution->getJobName()}/{$execution->getId()}/logs");

        self::assertResponseIsSuccessful();
        $expected($http->getResponse()->getContent());
    }

    public static function logs(): \Generator
    {
        yield [
            CountryJob::getJobName(),
            static function (string $content) {
                self::assertStringContainsString('Starting job {"job":"country"}', $content);
                self::assertStringContainsString('Job produced summary', $content);
                self::assertStringContainsString('"name":"France"', $content);
                self::assertStringContainsString('Job executed successfully {"job":"country"', $content);
            },
        ];
        yield [
            ImportStarWarsJob::getJobName(),
            static function (string $content) {
                self::assertStringContainsString('Starting job {"job":"star-wars.import"}', $content);

                self::assertStringContainsString('Starting child job {"job":"star-wars.import:planet"}', $content);
                self::assertStringContainsString(
                    'Job produced summary {"job":"star-wars.import:planet","read":61,"processed":60,"skipped":1,"invalid":1,"write":60}',
                    $content,
                );
                self::assertStringContainsString(
                    'Job executed successfully {"job":"star-wars.import:planet"',
                    $content,
                );

                self::assertStringContainsString('Starting child job {"job":"star-wars.import:specie"}', $content);
                self::assertStringContainsString(
                    'Job produced summary {"job":"star-wars.import:specie","read":37,"processed":37,"write":37}',
                    $content,
                );
                self::assertStringContainsString(
                    'Job executed successfully {"job":"star-wars.import:specie"',
                    $content,
                );

                self::assertStringContainsString('Starting child job {"job":"star-wars.import:character"}', $content);
                self::assertStringContainsString(
                    'Job produced summary {"job":"star-wars.import:character","read":87,"processed":87,"write":87}',
                    $content,
                );
                self::assertStringContainsString(
                    'Job executed successfully {"job":"star-wars.import:character"',
                    $content,
                );

                self::assertStringContainsString('Job executed successfully {"job":"star-wars.import"', $content);
            },
        ];
    }

    public static function assertSelectorAttributeSame(
        Crawler $crawler,
        string $selector,
        string $attribute,
        string $expected,
    ): void {
        self::assertSelectorExists($selector);
        $element = $crawler->filter($selector);
        self::assertSame($expected, $element->attr($attribute));
    }
}
