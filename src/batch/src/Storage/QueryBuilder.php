<?php

declare(strict_types=1);

namespace Yokai\Batch\Storage;

use Yokai\Batch\BatchStatus;
use Yokai\Batch\Exception\UnexpectedValueException;

/**
 * Fluent interface for building {@see Query}.
 *
 * Usage:
 *
 *     (new QueryBuilder())
 *         ->ids(['123', '456'])
 *         ->jobs(['export', 'import'])
 *         ->statuses([BatchStatus::RUNNING, BatchStatus::COMPLETED])
 *         ->sort(Query::SORT_BY_END_DESC)
 *         ->limit(6, 12)
 *         ->getQuery();
 *
 * Not an immutable object, can be used without chaining calls:
 *
 *     $builder = new QueryBuilder();
 *     $builder->ids(['123', '456']);
 *     $builder->jobs(['export', 'import']);
 *     $builder->statuses([BatchStatus::RUNNING, BatchStatus::COMPLETED]);
 *     $builder->sort(Query::SORT_BY_END_DESC);
 *     $builder->limit(6, 12);
 *     $builder->getQuery();
 */
final class QueryBuilder
{
    private const SORTS_ENUM = [
        Query::SORT_BY_START_ASC,
        Query::SORT_BY_START_DESC,
        Query::SORT_BY_END_ASC,
        Query::SORT_BY_END_DESC,
    ];

    private const STATUSES_ENUM = [
        BatchStatus::PENDING,
        BatchStatus::RUNNING,
        BatchStatus::STOPPED,
        BatchStatus::COMPLETED,
        BatchStatus::ABANDONED,
        BatchStatus::FAILED,
    ];

    /**
     * @var string[]
     */
    private array $jobNames = [];

    /**
     * @var string[]
     */
    private array $ids = [];

    /**
     * @var int[]
     */
    private array $statuses = [];

    private ?string $sortBy = null;

    private int $limit = 10;

    private int $offset = 0;

    /**
     * Filter executions that are for one of the given job names.
     *
     * @param string[] $names
     */
    public function jobs(array $names): self
    {
        $names = \array_unique($names);
        foreach ($names as $name) {
            if (!\is_string($name)) {
                throw UnexpectedValueException::type('string', $name);
            }
        }

        $this->jobNames = $names;

        return $this;
    }

    /**
     * Filter executions that are one of given ids.
     *
     * @param string[] $ids
     */
    public function ids(array $ids): self
    {
        $ids = \array_unique($ids);
        foreach ($ids as $id) {
            if (!\is_string($id)) {
                throw UnexpectedValueException::type('string', $id);
            }
        }

        $this->ids = $ids;

        return $this;
    }

    /**
     * Filter executions that are on given status.
     *
     * @param int[] $statuses Any of {@see BatchStatus::*}
     */
    public function statuses(array $statuses): self
    {
        $statuses = \array_unique($statuses);
        foreach ($statuses as $status) {
            if (!\in_array($status, self::STATUSES_ENUM, true)) {
                throw UnexpectedValueException::enum(self::STATUSES_ENUM, $status);
            }
        }

        $this->statuses = $statuses;

        return $this;
    }

    /**
     * Sort executions.
     *
     * @param string $by One of {@see QueryBuilder::SORT_BY_*}
     */
    public function sort(string $by): self
    {
        if (!\in_array($by, self::SORTS_ENUM, true)) {
            throw UnexpectedValueException::enum(self::SORTS_ENUM, $by);
        }

        $this->sortBy = $by;

        return $this;
    }

    /**
     * Limit query to a certain amount of executions.
     */
    public function limit(int $limit, int $offset): self
    {
        if ($limit < 1) {
            throw UnexpectedValueException::min(1, $limit);
        }
        if ($offset < 0) {
            throw UnexpectedValueException::min(0, $offset);
        }

        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * Build query from criteria in this builder.
     */
    public function getQuery(): Query
    {
        return new Query($this->jobNames, $this->ids, $this->statuses, $this->sortBy, $this->limit, $this->offset);
    }
}
