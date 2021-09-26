<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Validator;

use Iterator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Job\Item\Exception\SkipItemException;
use Yokai\Batch\Job\Item\ItemProcessorInterface;

final class SkipInvalidItemProcessor implements ItemProcessorInterface
{
    private ValidatorInterface $validator;

    /**
     * @var Constraint[]|null
     */
    private ?array $contraints;

    /**
     * @var string[]|null
     */
    private ?array $groups;

    /**
     * @param Constraint[]|null $contraints
     * @param string[]|null     $groups
     */
    public function __construct(ValidatorInterface $validator, array $contraints = null, array $groups = null)
    {
        $this->validator = $validator;
        $this->contraints = $contraints;
        $this->groups = $groups;
    }

    /**
     * @inheritDoc
     */
    public function process($item)
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
            yield \get_class($constraint);
        }
    }
}
