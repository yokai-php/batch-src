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
final class SkipInvalidItemProcessor implements ItemProcessorInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        /**
         * @var Constraint[]|null
         */
        private ?array $contraints = null,
        /**
         * @var string[]|null
         */
        private ?array $groups = null,
    ) {
    }

    public function process(mixed $item): mixed
    {
        $violations = $this->validator->validate($item, $this->contraints, $this->groups);
        if (\count($violations) === 0) {
            return $item;
        }

        throw new SkipItemException($item, new SkipItemOnViolations($violations), [
            'constraints' => \iterator_to_array($this->normalizeConstraints($this->contraints)),
            'groups' => $this->groups,
        ]);
    }

    /**
     * @param Constraint[]|null $constraints
     *
     * @phpstan-return Iterator<string>
     */
    private function normalizeConstraints(?array $constraints): Iterator
    {
        foreach ($constraints ?? [] as $constraint) {
            yield $constraint::class;
        }
    }
}
