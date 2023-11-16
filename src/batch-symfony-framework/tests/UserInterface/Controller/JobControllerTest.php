<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\UserInterface\Controller;

use PHPUnit\Framework\Constraint\LogicalAnd;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Test\Constraint as DomCrawlerConstraint;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\Type\FormTypeHttpFoundationExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Test\Constraint as ResponseConstraint;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Loader\XmlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Controller\JobController;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Form\JobFilterType;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\JobSecurity;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\ConfigurableTemplating;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\TemplatingInterface;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\TwigExtension;
use Yokai\Batch\Failure;
use Yokai\Batch\JobExecution;
use Yokai\Batch\JobExecutionLogs;
use Yokai\Batch\JobParameters;
use Yokai\Batch\Serializer\JsonJobExecutionSerializer;
use Yokai\Batch\Storage\FilesystemJobExecutionStorage;
use Yokai\Batch\Summary;
use Yokai\Batch\Warning;

final class JobControllerTest extends TestCase
{
    private const STORAGE_DIR = ARTIFACT_DIR . '/job-controller-jobs';

    private static FilesystemJobExecutionStorage $storage;

    public static function setUpBeforeClass(): void
    {
        self::$storage = new FilesystemJobExecutionStorage(new JsonJobExecutionSerializer(), self::STORAGE_DIR);
    }

    protected function setUp(): void
    {
        (new Filesystem())->remove(self::STORAGE_DIR);
    }

    /**
     * @dataProvider list
     */
    public function testList(
        \Closure $fixtures,
        Request $request,
        ?FormFactoryInterface $formFactory,
        JobSecurity $security,
        TemplatingInterface $templating,
        int $expectedStatus,
        int $expectedCountExecutions = 0,
        int $expectedCountPages = 0,
    ): void {
        $fixtures();

        $response = $this->response(
            fn(JobController $controller) => $controller->list($request),
            $formFactory,
            $security,
            $templating,
        );

        self::assertResponseStatusCodeSame($response, $expectedStatus);
        if ($expectedStatus === Response::HTTP_OK) {
            $page = new Crawler((string)$response->getContent());
            self::assertCount($expectedCountExecutions, $page->filter('.job-list > tbody > tr'));
            self::assertCount($expectedCountPages, $page->filter('.pagination a[href]'));
        }
    }

    public static function list(): \Generator
    {
        foreach (self::formFactories() as $formFactory) {
            foreach (self::securities() as [$security, $granted]) {
                $status = $granted ? Response::HTTP_OK : Response::HTTP_FORBIDDEN;
                foreach (self::templatings() as $templating) {
                    yield [
                        fn() => self::fixtures(30),
                        Request::create('/jobs'),
                        $formFactory,
                        $security,
                        $templating,
                        $status,
                        20,
                        1,
                    ];
                    yield [
                        fn() => self::fixtures(30),
                        Request::create('/jobs?sort=end_asc'),
                        $formFactory,
                        $security,
                        $templating,
                        $status,
                        20,
                        1,
                    ];
                    yield [
                        fn() => null,
                        Request::create('/jobs?sort=unknown'),
                        $formFactory,
                        $security,
                        $templating,
                        $granted ? Response::HTTP_BAD_REQUEST : $status,
                    ];
                    yield [
                        fn() => self::fixtures(30),
                        Request::create('/jobs?page=2'),
                        $formFactory,
                        $security,
                        $templating,
                        $status,
                        10,
                        1,
                    ];

                    // filtering is only possible when symfony/form is installed
                    if ($formFactory !== null) {
                        yield [
                            function () {
                                self::fixtures(10, ['jobName' => 'export']);
                                self::fixtures(10, ['jobName' => 'import']);
                            },
                            Request::create('/jobs?filter[jobs][]=export'),
                            $formFactory,
                            $security,
                            $templating,
                            $status,
                            10,
                            0,
                        ];
                        yield [
                            function () {
                                self::fixtures(6, ['status' => BatchStatus::PENDING]);
                                self::fixtures(4, ['status' => BatchStatus::RUNNING]);
                                self::fixtures(10, ['status' => BatchStatus::COMPLETED]);
                            },
                            Request::create('/jobs?filter[statuses][]=1'),
                            $formFactory,
                            $security,
                            $templating,
                            $status,
                            6,
                            0,
                        ];
                        yield [
                            function () {
                                self::fixtures(30, ['jobName' => 'export', 'status' => BatchStatus::PENDING]);
                                self::fixtures(5, ['jobName' => 'import']);
                                self::fixtures(5, ['status' => BatchStatus::COMPLETED]);
                            },
                            Request::create('/jobs?filter[jobs][]=export&filter[statuses][]=1&page=2'),
                            $formFactory,
                            $security,
                            $templating,
                            $status,
                            10,
                            1,
                        ];
                    }
                }
            }
        }
    }

    /**
     * @dataProvider view
     */
    public function testView(
        \Closure $fixtures,
        string $job,
        string $id,
        ?string $path,
        JobSecurity $security,
        TemplatingInterface $templating,
        int $expectedStatus,
        array $expected = [],
    ): void {
        $fixtures();

        $response = $this->response(
            fn(JobController $controller) => $controller->view($job, $id, $path),
            null,
            $security,
            $templating,
        );

        self::assertResponseStatusCodeSame($response, $expectedStatus);
        if ($expectedStatus === Response::HTTP_OK) {
            $page = new Crawler((string)$response->getContent());
            foreach ($expected as $value) {
                self::assertSelectorTextContains($page, '.job-show', $value);
            }
        }
    }

    public static function view(): \Generator
    {
        foreach (self::securities() as [$security, $granted]) {
            $status = $granted ? Response::HTTP_OK : Response::HTTP_FORBIDDEN;
            foreach (self::templatings() as $templating) {
                $jobWithChildrenFixtures = function () {
                    $exportExecution = JobExecution::createRoot(
                        '64edbe399b58e',
                        'export',
                        new BatchStatus(BatchStatus::COMPLETED),
                        new JobParameters(['type' => 'complete']),
                        new Summary(['count' => 156]),
                    );
                    $exportExecution->addWarning(new Warning('Skipped suspicious record', [], ['suspicious_record' => 2]));
                    $exportExecution->addFailure(new Failure('RuntimeException', 'Missing record #2', 0));
                    $exportExecution->setStartTime(new \DateTimeImmutable('2021-01-01 10:00'));
                    $exportExecution->setEndTime(new \DateTimeImmutable('2021-01-01 11:00'));
                    $exportExecution->addChildExecution(
                        JobExecution::createChild(
                            $exportExecution,
                            'download',
                            new BatchStatus(BatchStatus::COMPLETED),
                        ),
                    );
                    $exportExecution->addChildExecution(
                        JobExecution::createChild(
                            $exportExecution,
                            'transform',
                            new BatchStatus(BatchStatus::RUNNING),
                        ),
                    );
                    $exportExecution->addChildExecution(
                        JobExecution::createChild(
                            $exportExecution,
                            'upload',
                            new BatchStatus(BatchStatus::PENDING),
                        ),
                    );
                    self::$storage->store($exportExecution);
                };
                yield [
                    $jobWithChildrenFixtures,
                    'export',
                    '64edbe399b58e',
                    null,
                    $security,
                    $templating,
                    $status,
                    [
                        'Execution ID 64edbe399b58e',
                        'Job name job.job_name.export',
                        'Status Completed',
                        'Start time January 1, 2021 10:00',
                        'End time January 1, 2021 11:00',
                        '"type": "complete"',
                        '"count": 156',
                        'Skipped suspicious record',
                        '"suspicious_record": 2',
                        'RuntimeException',
                        'Missing record #2',
                    ],
                ];
                yield [
                    $jobWithChildrenFixtures,
                    'export',
                    '64edbe399b58e',
                    'transform',
                    $security,
                    $templating,
                    $status,
                    [
                        'Execution ID 64edbe399b58e',
                        'Job name job.job_name.transform',
                        'Status Running',
                    ],
                ];
                yield [
                    $jobWithChildrenFixtures,
                    'export',
                    '64edbe399b58e',
                    'unknown.children',
                    $security,
                    $templating,
                    $status === Response::HTTP_OK ? Response::HTTP_NOT_FOUND : $status,
                ];
                yield [
                    fn() => null,
                    'job.unknown',
                    'unknown_id',
                    null,
                    $security,
                    $templating,
                    Response::HTTP_NOT_FOUND,
                ];
            }
        }
    }

    /**
     * @dataProvider logs
     */
    public function testLogs(
        \Closure $fixtures,
        string $job,
        string $id,
        JobSecurity $security,
        int $expectedStatus,
        string $expectedLogs = '',
    ): void {
        $fixtures();

        $response = $this->response(
            fn(JobController $controller) => $controller->logs($job, $id),
            null,
            $security,
            new ConfigurableTemplating('unused', []),
        );

        self::assertSame($expectedStatus, $response->getStatusCode());
        if ($expectedStatus === Response::HTTP_OK) {
            self::assertSame("attachment; filename={$job}-{$id}.log", $response->headers->get('Content-Disposition'));
            self::assertSame('application/log', $response->headers->get('Content-Type'));
            self::assertSame($expectedLogs, $response->getContent());
        }
    }

    public static function logs(): \Generator
    {
        foreach (self::securities() as [$security, $granted]) {
            $status = $granted ? Response::HTTP_OK : Response::HTTP_FORBIDDEN;
            yield [
                function () {
                    $execution = JobExecution::createRoot(
                        '64f1f6d5e7e18',
                        'export',
                        logs: new JobExecutionLogs(
                            <<<LOG
                            [2021-01-01T10:00:00.000000+01:00] INFO: Lorem ipsum []
                            [2021-01-01T10:30:00.000000+01:00] DEBUG: Dolor sit amet []
                            LOG,
                        ),
                    );
                    self::$storage->store($execution);
                },
                'export',
                '64f1f6d5e7e18',
                $security,
                $status,
                <<<LOG
                [2021-01-01T10:00:00.000000+01:00] INFO: Lorem ipsum []
                [2021-01-01T10:30:00.000000+01:00] DEBUG: Dolor sit amet []
                LOG,
            ];
            yield [
                fn() => null,
                'job.unknown',
                'unknown_id',
                $security,
                Response::HTTP_NOT_FOUND,
            ];
        }
    }

    /**
     * @return \Generator<FormFactoryInterface|null>
     */
    private static function formFactories(): \Generator
    {
        yield null;
        yield Forms::createFormFactoryBuilder()
            ->addExtensions([
                new CsrfExtension(new CsrfTokenManager()),
            ])
            ->addTypeExtensions([
                new FormTypeHttpFoundationExtension(),
            ])
            ->addTypes([
                new JobFilterType(['export', 'import']),
            ])
            ->getFormFactory();
    }

    /**
     * @return \Generator<array{0: JobSecurity, 1: bool}>
     */
    private static function securities(): \Generator
    {
        foreach ([true, false] as $granted) {
            yield [
                new JobSecurity(
                    new class($granted) implements AuthorizationCheckerInterface {
                        public function __construct(private bool $granted)
                        {
                        }

                        public function isGranted(mixed $attribute, mixed $subject = null): bool
                        {
                            return $this->granted;
                        }
                    },
                    'ROLE_UNUSED',
                    'ROLE_UNUSED',
                    'ROLE_UNUSED',
                    'ROLE_UNUSED',
                ),
                $granted,
            ];
        }
    }

    /**
     * @return \Generator<TemplatingInterface>
     */
    private static function templatings(): \Generator
    {
        yield new ConfigurableTemplating('@YokaiBatch/bootstrap4', ['base_template' => 'base.html.twig']);
        //todo one day, test with sonata, but it is too much pain to setup actually
    }

    /**
     * @param \Closure(JobController): Response $closure
     */
    private function response(
        \Closure $closure,
        ?FormFactoryInterface $formFactory,
        JobSecurity $security,
        TemplatingInterface $templating,
    ): Response {
        try {
            return $closure($this->controller($formFactory, $security, $templating));
        } catch (HttpException $exception) {
            return new Response(status: $exception->getStatusCode());
        } catch (AccessDeniedException) {
            return new Response(status: Response::HTTP_FORBIDDEN);
        }
    }

    private function controller(
        ?FormFactoryInterface $formFactory,
        JobSecurity $security,
        TemplatingInterface $templating,
    ): JobController {
        $twig = new Environment(
            $loader = new FilesystemLoader(
                \array_filter([
                    __DIR__ . '/templates',
                    __DIR__ . '/../../../../../vendor/symfony/twig-bridge/Resources/views/Form',
                    __DIR__ . '/../../../vendor/symfony/twig-bridge/Resources/views/Form',
                ], 'is_dir'),
            ),
        );
        $loader->addPath(__DIR__ . '/../../../src/Resources/views', 'YokaiBatch');
        $translator = new Translator('en');
        $translator->addLoader('xlf', new XliffFileLoader());
        $translator->addResource(
            'xlf',
            __DIR__ . '/../../../src/Resources/translations/YokaiBatchBundle.en.xlf',
            'en',
            'YokaiBatchBundle',
        );
        $twig->addExtension(new TranslationExtension($translator));
        $twig->addExtension(new FormExtension());
        $twig->addExtension(
            new RoutingExtension(
                new UrlGenerator(
                    (new XmlFileLoader(new FileLocator()))->load(__DIR__ . '/../../../src/Resources/routing/ui.xml'),
                    new RequestContext(),
                ),
            ),
        );
        $twig->addExtension(new TwigExtension($security));
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => fn() => new FormRenderer(
                new TwigRendererEngine(['bootstrap_4_layout.html.twig'], $twig),
                new CsrfTokenManager(),
            ),
        ]));

        return new JobController(self::$storage, $formFactory, $security, $twig, $templating);
    }

    private static function fixtures(int $count, array $attributes = []): void
    {
        for ($i = 0; $i < $count; $i++) {
            $values = \array_merge(
                [
                    'id' => \uniqid(),
                    'jobName' => \array_rand(\array_flip(['export', 'import'])),
                    'status' => \array_rand(\array_flip([
                        BatchStatus::PENDING,
                        BatchStatus::RUNNING,
                        BatchStatus::COMPLETED,
                        BatchStatus::FAILED,
                    ])),
                    'startTime' => $start = (new \DateTimeImmutable())->setTimestamp(\random_int(0, \time() - 10)),
                    'endTime' => (new \DateTimeImmutable())->setTimestamp(\random_int($start->getTimestamp(), \time() - 10)),
                ],
                $attributes,
            );

            $execution = JobExecution::createRoot(
                $values['id'],
                $values['jobName'],
                new BatchStatus($values['status']),
            );
            if (!$execution->getStatus()->is(BatchStatus::PENDING)) {
                $execution->setStartTime($values['startTime']);
                if (!$execution->getStatus()->is(BatchStatus::RUNNING)) {
                    $execution->setEndTime($values['endTime']);
                }
            }

            self::$storage->store($execution);
        }
    }

    private static function assertSelectorTextContains(Crawler $crawler, string $selector, string $text): void
    {
        self::assertThat($crawler, LogicalAnd::fromConstraints(
            new DomCrawlerConstraint\CrawlerSelectorExists($selector),
            new DomCrawlerConstraint\CrawlerSelectorTextContains($selector, $text)
        ));
    }

    private static function assertResponseStatusCodeSame(Response $response, int $expectedCode): void
    {
        self::assertThat($response, new ResponseConstraint\ResponseStatusCodeSame($expectedCode));
    }
}
