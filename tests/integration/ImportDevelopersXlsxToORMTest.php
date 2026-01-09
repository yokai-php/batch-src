<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectWriter;
use Yokai\Batch\Bridge\OpenSpout\Reader\FlatFileReader;
use Yokai\Batch\Bridge\OpenSpout\Reader\HeaderStrategy;
use Yokai\Batch\Job\Item\ItemJob;
use Yokai\Batch\Job\Item\Processor\CallbackProcessor;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Job\JobWithChildJobs;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Sources\Tests\Integration\Entity\Badge;
use Yokai\Batch\Sources\Tests\Integration\Entity\Developer;
use Yokai\Batch\Sources\Tests\Integration\Entity\Repository;
use Yokai\Batch\Sources\Tests\Integration\Job\SplitDeveloperXlsxJob;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

class ImportDevelopersXlsxToORMTest extends JobTestCase
{
    private const OUTPUT_BASE_DIR = self::OUTPUT_DIR . '/multi-tab-xlsx-to-objects';
    private const OUTPUT_BADGE_FILE = self::OUTPUT_BASE_DIR . '/badge.csv';
    private const OUTPUT_REPOSITORY_FILE = self::OUTPUT_BASE_DIR . '/repository.csv';
    private const OUTPUT_DEVELOPER_FILE = self::OUTPUT_BASE_DIR . '/developer.csv';
    private const INPUT_FILE = __DIR__ . '/fixtures/multi-tab-xlsx-to-objects.xslx';

    private EntityManager $entityManager;

    private MockObject&ManagerRegistry $doctrine;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Entity'], true);
        if (\PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        } else {
            $config->setProxyDir(\sys_get_temp_dir());
            $config->setProxyNamespace('DoctrineProxies');
        }
        $connection = DriverManager::getConnection((new DsnParser())->parse(\getenv('DATABASE_URL')));
        $this->entityManager = new EntityManager($connection, $config);

        (new SchemaTool($this->entityManager))
            ->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->doctrine->method('getManagerForClass')
            ->willReturn($this->entityManager);
    }

    protected function getJobName(): string
    {
        return 'multi-tab-xlsx-to-objects';
    }

    protected function createJob(JobExecutionStorageInterface $executionStorage): JobInterface
    {
        $entityManager = $this->entityManager;
        $objectWriter = new ObjectWriter($this->doctrine);

        $inputFile = self::INPUT_FILE;
        $outputBadgeFile = self::OUTPUT_BADGE_FILE;
        $outputRepositoryFile = self::OUTPUT_REPOSITORY_FILE;
        $outputDeveloperFile = self::OUTPUT_DEVELOPER_FILE;

        $csvReader = fn(string $file): FlatFileReader => new FlatFileReader(
            filePath: new StaticValueParameterAccessor($file),
            headerStrategy: HeaderStrategy::combine(),
        );

        return new JobWithChildJobs(
            $executionStorage,
            self::createJobExecutor($executionStorage, [
                'split' => new SplitDeveloperXlsxJob(
                    $inputFile,
                    $outputBadgeFile,
                    $outputRepositoryFile,
                    $outputDeveloperFile,
                ),
                'import' => new JobWithChildJobs(
                    $executionStorage,
                    self::createJobExecutor($executionStorage, [
                        'import-badge' => new ItemJob(
                            PHP_INT_MAX,
                            $csvReader(self::OUTPUT_BADGE_FILE),
                            new CallbackProcessor(function (array $item) {
                                $badge = new Badge();
                                $badge->label = $item['label'];
                                $badge->rank = (int)$item['rank'];

                                return $badge;
                            }),
                            $objectWriter,
                            $executionStorage,
                        ),
                        'import-repository' => new ItemJob(
                            PHP_INT_MAX,
                            $csvReader(self::OUTPUT_REPOSITORY_FILE),
                            new CallbackProcessor(function (array $item) {
                                $repository = new Repository();
                                $repository->label = $item['label'];
                                $repository->url = $item['url'];

                                return $repository;
                            }),
                            $objectWriter,
                            $executionStorage,
                        ),
                        'import-developer' => new ItemJob(
                            5,
                            $csvReader(self::OUTPUT_DEVELOPER_FILE),
                            new CallbackProcessor(function (array $item) use ($entityManager) {
                                $badges = $entityManager->getRepository(Badge::class)
                                    ->findBy(['label' => \str_getcsv((string)$item['badges'], '|', '"', '\\')]);
                                $repositories = $entityManager->getRepository(Repository::class)
                                    ->findBy(['label' => \str_getcsv((string)$item['repositories'], '|', '"', '\\')]);

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
                            }),
                            $objectWriter,
                            $executionStorage,
                        ),
                    ]),
                    ['import-badge', 'import-repository', 'import-developer'],
                ),
            ]),
            ['split', 'import'],
        );
    }

    protected function assertAgainstExecution(
        JobExecutionStorageInterface $jobExecutionStorage,
        JobExecution $jobExecution,
    ): void {
        parent::assertAgainstExecution($jobExecutionStorage, $jobExecution);

        self::assertFalse($jobExecution->getStatus()->isUnsuccessful());

        $importJobExecution = $jobExecution->getChildExecution('import');

        $expectedCountBadges = 27;
        $importBadgeSummary = $importJobExecution->getChildExecution('import-badge')->getSummary();
        self::assertSame($expectedCountBadges, $importBadgeSummary->get('read'));
        self::assertSame($expectedCountBadges, $importBadgeSummary->get('processed'));
        self::assertSame($expectedCountBadges, $importBadgeSummary->get('write'));
        self::assertSame($expectedCountBadges, $this->entityManager->getRepository(Badge::class)->count([]));

        $expectedCountRepositories = 3;
        $importRepositorySummary = $importJobExecution->getChildExecution('import-repository')->getSummary();
        self::assertSame($expectedCountRepositories, $importRepositorySummary->get('read'));
        self::assertSame($expectedCountRepositories, $importRepositorySummary->get('processed'));
        self::assertSame($expectedCountRepositories, $importRepositorySummary->get('write'));
        self::assertSame($expectedCountRepositories, $this->entityManager->getRepository(Repository::class)->count([]));

        $expectedCountDevelopers = 20;
        $importDeveloperSummary = $importJobExecution->getChildExecution('import-developer')->getSummary();
        self::assertSame($expectedCountDevelopers, $importDeveloperSummary->get('read'));
        self::assertSame($expectedCountDevelopers, $importDeveloperSummary->get('processed'));
        self::assertSame($expectedCountDevelopers, $importDeveloperSummary->get('write'));
        self::assertSame($expectedCountDevelopers, $this->entityManager->getRepository(Developer::class)->count([]));
    }
}
