<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Validator;

use Iterator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Job\Item\Exception\SkipItemException;
use Yokai\Batch\Job\Item\ItemProcessorInterface;

/**
 * This {@see ItemProcessorInterface} uses Symfony's validator to validate items.
 * When an item is not valid, it throw a {@see SkipItemException} with a {@see SkipItemOnViolations} cause.
 */
final readonly class SkipInvalidItemProcessor implements ItemProcessorInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        /**
         * @var Constraint[]|null
         */
        private array|null $constraints = null,
        /**
         * @var string[]|null
         */
        private array|null $groups = null,
    ) {
    }

    public function process(mixed $item): mixed
    {
        $violations = $this->validator->validate($item, $this->constraints, $this->groups);
        if (\count($violations) === 0) {
            return $item;
        }

        throw new SkipItemException($item, new SkipItemOnViolations($violations), [
            'constraints' => \iterator_to_array($this->normalizeConstraints($this->constraints)),
            'groups' => $this->groups,
        ]);
    }

    /**
     * @param Constraint[]|null $constraints
     *
     * @return Iterator<string>
     */
    private function normalizeConstraints(null|array $constraints): Iterator
    {
        foreach ($constraints ?? [] as $constraint) {
            yield $constraint::class;
        }
    }
}
