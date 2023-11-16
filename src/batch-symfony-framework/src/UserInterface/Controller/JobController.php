<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Controller;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Twig\Environment;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Form\JobFilter;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Form\JobFilterType;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\JobSecurity;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Templating\TemplatingInterface;
use Yokai\Batch\Exception\JobExecutionNotFoundException;
use Yokai\Batch\JobExecution;
use Yokai\Batch\Storage\Query;
use Yokai\Batch\Storage\QueryableJobExecutionStorageInterface;
use Yokai\Batch\Storage\QueryBuilder;

/**
 * Controller handling HTTP layer of user interface.
 */
final class JobController
{
    private const LIMIT = 20;

    public function __construct(
        private QueryableJobExecutionStorageInterface $jobExecutionStorage,
        private ?FormFactoryInterface $formFactory,
        private JobSecurity $security,
        private Environment $twig,
        private TemplatingInterface $templating,
    ) {
    }

    /**
     * List/filter {@see JobExecution} and view it in a Twig template.
     */
    public function list(Request $request): Response
    {
        $this->security->denyAccessUnlessGrantedList();

        $page = $request->query->getInt('page', 1);
        $sort = (string)$request->query->get('sort', Query::SORT_BY_START_DESC);

        $query = new QueryBuilder();

        $filter = null;
        $filters = null;
        if ($this->formFactory !== null) {
            $filter = $this->formFactory->createNamed(
                'filter',
                JobFilterType::class,
                $data = new JobFilter(),
                [
                    'method' => Request::METHOD_GET,
                    'csrf_protection' => false,
                ]
            );
            $filter->handleRequest($request);

            $query->jobs($data->jobs);
            $query->statuses($data->statuses);

            // find out which filters were used
            $filters = [];
            foreach (\get_object_vars($data) as $field => $value) {
                if ($value !== []) {
                    $filters[] = $field;
                }
            }
        }

        try {
            $query->limit(self::LIMIT, self::LIMIT * ($page - 1));
            $query->sort($sort);
        } catch (Throwable $exception) {
            throw new BadRequestHttpException(previous: $exception);
        }

        // transform iterable executions to array
        $executions = [];
        foreach ($this->jobExecutionStorage->query($query->getQuery()) as $execution) {
            $executions[] = $execution;
        }

        // prepare sort variable for view
        $sort = [
            'parameter' => 'sort',
            'current' => $sort,
            'desc' => \in_array($sort, [Query::SORT_BY_START_DESC, Query::SORT_BY_END_DESC], true),
            // sort by execution start info
            'start' => [
                'switch' => $sort === Query::SORT_BY_START_DESC ? Query::SORT_BY_START_ASC : Query::SORT_BY_START_DESC,
                'sorted' => \in_array($sort, [Query::SORT_BY_START_ASC, Query::SORT_BY_START_DESC], true),
            ],
            // sort by execution end info
            'end' => [
                'switch' => $sort === Query::SORT_BY_END_DESC ? Query::SORT_BY_END_ASC : Query::SORT_BY_END_DESC,
                'sorted' => \in_array($sort, [Query::SORT_BY_END_ASC, Query::SORT_BY_END_DESC], true),
            ],
        ];
        // prepare pagination variable for view
        $pagination = [
            'parameter' => 'page',
            'per_page' => self::LIMIT,
            'results' => \count($executions),
            'current' => $page,
            'is' => [
                'first' => $page === 1,
                'last' => \count($executions) !== self::LIMIT,
            ],
            'prev' => ['enabled' => $page !== 1, 'value' => $page - 1],
            'next' => ['enabled' => \count($executions) === self::LIMIT, 'value' => $page + 1],
        ];

        return new Response(
            $this->twig->render(
                $this->templating->name('list.html.twig'),
                $this->templating->context([
                    'executions' => $executions,
                    'form' => $filter?->createView(),
                    'sort' => $sort,
                    'filters' => $filters,
                    'pagination' => $pagination,
                ]),
            ),
        );
    }

    /**
     * View {@see JobExecution} details in a Twig template.
     */
    public function view(string $job, string $id, ?string $path = null): Response
    {
        try {
            $execution = $this->jobExecutionStorage->retrieve($job, $id);
        } catch (JobExecutionNotFoundException $exception) {
            throw new NotFoundHttpException(previous: $exception);
        }

        $this->security->denyAccessUnlessGrantedView($execution);

        $executionsPath = [
            '' => $execution,
        ];
        if ($path !== null) {
            $parentPath = [];
            foreach (\explode('|', $path) as $childName) {
                $execution = $execution->getChildExecution($childName) ?? throw new NotFoundHttpException();
                $parentPath[] = $childName;
                $executionsPath[\implode('|', $parentPath)] = $execution;
            }
        }

        $pathPrefix = '';
        if ($path !== null) {
            $pathPrefix = $path . '|';
        }

        return new Response(
            $this->twig->render(
                $this->templating->name('show.html.twig'),
                $this->templating->context([
                    'execution' => $execution,
                    'pathPrefix' => $pathPrefix,
                    'executionsPath' => $executionsPath,
                ]),
            ),
        );
    }

    /**
     * Download {@see JobExecution} logs.
     */
    public function logs(string $job, string $id): Response
    {
        try {
            $execution = $this->jobExecutionStorage->retrieve($job, $id);
        } catch (JobExecutionNotFoundException $exception) {
            throw new NotFoundHttpException(previous: $exception);
        }

        $this->security->denyAccessUnlessGrantedLogs($execution);

        $filename = \sprintf('%s-%s.log', $execution->getJobName(), $execution->getId());
        $response = new Response((string)$execution->getLogs());
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
        );
        $response->headers->set('Content-Type', 'application/log');

        return $response;
    }
}
