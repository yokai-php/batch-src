<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Validator;

use Composer\InstalledVersions;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Yokai\Batch\Bridge\Symfony\Validator\SkipInvalidItemProcessor;
use Yokai\Batch\Job\Item\InvalidItemException;
use Yokai\Batch\Tests\Bridge\Symfony\Validator\Fixtures\ObjectWithAnnotationValidation;

class SkipInvalidItemProcessorTest extends TestCase
{
    private static ValidatorInterface $validator;

    public static function setUpBeforeClass(): void
    {
        if (\version_compare(InstalledVersions::getVersion('symfony/validator'), '5.0.0') >= 0) {
            self::$validator = Validation::createValidatorBuilder()
                ->enableAnnotationMapping(true)
                ->addDefaultDoctrineAnnotationReader()
                ->getValidator();
        } else {
            // @codeCoverageIgnoreStart
            // Symfony 4.x compatibility
            self::$validator = Validation::createValidatorBuilder()
                ->enableAnnotationMapping(new AnnotationReader())
                ->getValidator();
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @dataProvider groups
     */
    public function testProcessValid(?array $groups): void
    {
        $processor = new SkipInvalidItemProcessor(self::$validator, [new NotBlank(['groups' => $groups])], $groups);
        self::assertSame('valid item not blank', $processor->process('valid item not blank'));
    }

    /**
     * @dataProvider groups
     */
    public function testProcessInvalid(?array $groups): void
    {
        $this->expectException(InvalidItemException::class);

        $processor = new SkipInvalidItemProcessor(self::$validator, [new Blank(['groups' => ['Default', 'Full']])], $groups);
        $processor->process('invalid item not blank');
    }

    /**
     * @dataProvider groups
     */
    public function testProcessNormalization(?array $groups): void
    {
        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessageMatches(
            <<<REGEXP
#^emptyString: This value should be null\.: ""
null: This value should not be null\.: NULL
string: This value should be null\.: string
int: This value should be null\.: 1
date: This value should be null\.: 2021-09-23T12:09:32\+0200
array: This collection should contain exactly 0 elements\.: 1, 2
objectStringable: This value should be null\.: /.+/tests/Fixtures/ObjectWithAnnotationValidation\.php
objectNotStringable: This value should be null\.: class@anonymous.+
valueWithoutInterpretation: This value should be null\.: resource$#
REGEXP
        );

        $processor = new SkipInvalidItemProcessor(self::$validator, null, $groups);
        $processor->process(new ObjectWithAnnotationValidation());
    }

    public function groups()
    {
        yield 'No groups specified' => [null];
        yield 'Group "Full" only' => [['Full']];
    }
}
