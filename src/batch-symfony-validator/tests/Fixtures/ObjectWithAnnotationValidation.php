<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Validator\Fixtures;

use DateTimeImmutable;
use SplFileInfo;
use Symfony\Component\Validator\Constraints as Assert;

final class ObjectWithAnnotationValidation
{
    /**
     * @Assert\IsNull(groups={"Default", "Full"})
     */
    public string $emptyString;

    /**
     * @Assert\NotNull(groups={"Default", "Full"})
     */
    public ?string $null;

    /**
     * @Assert\IsNull(groups={"Default", "Full"})
     */
    public string $string;

    /**
     * @Assert\IsNull(groups={"Default", "Full"})
     */
    public int $int;

    /**
     * @Assert\IsNull(groups={"Default", "Full"})
     */
    public DateTimeImmutable $date;

    /**
     * @Assert\Count(max=0, groups={"Default", "Full"})
     */
    public array $array;

    /**
     * @Assert\IsNull(groups={"Default", "Full"})
     */
    public SplFileInfo $objectStringable;

    /**
     * @Assert\IsNull(groups={"Default", "Full"})
     */
    public object $objectNotStringable;

    /**
     * @Assert\IsNull(groups={"Default", "Full"})
     * @var resource
     */
    public $valueWithoutInterpretation;

    public function __construct()
    {
        $this->emptyString = '';
        $this->null = null;
        $this->string = 'string';
        $this->int = 1;
        $this->date = new DateTimeImmutable('2021-09-23T12:09:32+0200');
        $this->array = [1, 2];
        $this->objectStringable = new SplFileInfo(__FILE__);
        $this->objectNotStringable = new class {
        };
        $this->valueWithoutInterpretation = \fopen(__FILE__, 'r');
    }
}
