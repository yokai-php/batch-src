<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Integration;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Yokai\Batch\Bridge\Box\Spout\Reader\FlatFileReader;
use Yokai\Batch\Bridge\Box\Spout\Reader\HeaderStrategy;
use Yokai\Batch\Bridge\Box\Spout\Reader\Options\CSVOptions;
use Yokai\Batch\Bridge\Doctrine\Persistence\ObjectWriter;
use Yokai\Batch\Job\Item\ItemJob;
use Yokai\Batch\Job\JobInterface;
use Yokai\Batch\Job\JobWithChildJobs;
use Yokai\Batch\Job\Parameters\StaticValueParameterAccessor;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Sources\Tests\Integration\Entity\Badge;
use Yokai\Batch\Sources\Tests\Integration\Entity\Developer;
use Yokai\Batch\Sources\Tests\Integration\Entity\Repository;
use Yokai\Batch\Sources\Tests\Integration\Job\SplitDeveloperXlsxJob;
use Yokai\Batch\Sources\Tests\Integration\Processor\BadgeProcessor;
use Yokai\Batch\Sources\Tests\Integration\Processor\DeveloperProcessor;
use Yokai\Batch\Sources\Tests\Integration\Processor\RepositoryProcessor;
use Yokai\Batch\Storage\JobExecutionStorageInterface;

class ImportDevelopersXlsxToORMTest extends JobTestCase
{
    use ProphecyTrait;

    private const OUTPUT_BASE_DIR = self::OUTPUT_DIR . '/multi-tab-xlsx-to-objects';
    private const OUTPUT_BADGE_FILE = self::OUTPUT_BASE_DIR . '/badge.csv';
    private const OUTPUT_REPOSITORY_FILE = self::OUTPUT_BASE_DIR . '/repository.csv';
    private const OUTPUT_DEVELOPER_FILE = self::OUTPUT_BASE_DIR . '/developer.csv';
    private const INPUT_FILE = __DIR__ . '/fixtures/multi-tab-xlsx-to-objects.xslx';

    private $persisted;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ManagerRegistry|ObjectProphecy
     */
    private $doctrine;

    protected function setUp(): void
    {
        $this->persisted = [];

        $connection = DriverManager::getConnection(['url' => \getenv('DATABASE_URL')]);
        $config = ORMSetup::createAnnotationMetadataConfiguration([__DIR__ . '/Entity'], true);
        $this->entityManager = new EntityManager($connection, $config);

        (new SchemaTool($this->entityManager))
            ->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());

        $this->doctrine = $this->prophesize(ManagerRegistry::class);
        $this->doctrine->getManagerForClass(Argument::any())
            ->willReturn($this->entityManager);
    }

    protected function getJobName(): string
    {
        return 'multi-tab-xlsx-to-objects';
    }

    protected function createJob(JobExecutionStorageInterface $executionStorage): JobInterface
    {
        $entityManager = $this->entityManager;
        $objectWriter = new ObjectWriter($this->doctrine->reveal());

        $inputFile = self::INPUT_FILE;
        $outputBadgeFile = self::OUTPUT_BADGE_FILE;
        $outputRepositoryFile = self::OUTPUT_REPOSITORY_FILE;
        $outputDeveloperFile = self::OUTPUT_DEVELOPER_FILE;

        $csvReader = function (string $file): FlatFileReader {
            return new FlatFileReader(
                new StaticValueParameterAccessor($file),
                new CSVOptions(),
                HeaderStrategy::combine()
            );
        };

        return new JobWithChildJobs(
            $executionStorage,
            self::createJobExecutor($executionStorage, [
                'split' => new SplitDeveloperXlsxJob(
                    $inputFile,
                    $outputBadgeFile,
                    $outputRepositoryFile,
                    $outputDeveloperFile
                ),
                'import' => new JobWithChildJobs(
                    $executionStorage,
                    self::createJobExecutor($executionStorage, [
                        'import-badge' => new ItemJob(
                            PHP_INT_MAX,
                            $csvReader(self::OUTPUT_BADGE_FILE),
                            new BadgeProcessor(),
                            $objectWriter,
                            $executionStorage
                        ),
                        'import-repository' => new ItemJob(
                            PHP_INT_MAX,
                            $csvReader(self::OUTPUT_REPOSITORY_FILE),
                            new RepositoryProcessor(),
                            $objectWriter,
                            $executionStorage
                        ),
                        'import-developer' => new ItemJob(
                            5,
                            $csvReader(self::OUTPUT_DEVELOPER_FILE),
                            new DeveloperProcessor($entityManager),
                            $objectWriter,
                            $executionStorage
                        ),
                    ]),
                    ['import-badge', 'import-repository', 'import-developer']
                ),
            ]),
            ['split', 'import']
        );
    }

    protected function assertAgainstExecution(
        JobExecutionStorageInterface $jobExecutionStorage,
        JobExecution $jobExecution
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
