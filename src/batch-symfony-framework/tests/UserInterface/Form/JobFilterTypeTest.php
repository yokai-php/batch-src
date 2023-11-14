<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\UserInterface\Form;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;
use Yokai\Batch\BatchStatus;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Form\JobFilter;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Form\JobFilterType;

final class JobFilterTypeTest extends TypeTestCase
{
    public function testBuild(): void
    {
        $form = $this->factory->create(JobFilterType::class, new JobFilter());
        $view = $form->createView();

        $choices = function (FormView $view) {
            $choices = [];
            /** @var ChoiceView $choice */
            foreach ($view->vars['choices'] as $choice) {
                $choices[(string)$choice->label] = $choice->data;
            }

            return $choices;
        };

        self::assertSame(
            [
                'job.job_name.export' => 'export',
                'job.job_name.import' => 'import',
            ],
            $choices($view->children['jobs']),
        );
        self::assertSame(
            [
                'job.status.pending' => BatchStatus::PENDING,
                'job.status.running' => BatchStatus::RUNNING,
                'job.status.stopped' => BatchStatus::STOPPED,
                'job.status.completed' => BatchStatus::COMPLETED,
                'job.status.abandoned' => BatchStatus::ABANDONED,
                'job.status.failed' => BatchStatus::FAILED,
            ],
            $choices($view->children['statuses']),
        );
    }

    /**
     * @dataProvider submit
     */
    public function testSubmit(array $submit, JobFilter $expected, bool $valid): void
    {
        $form = $this->factory->create(JobFilterType::class, $actual = new JobFilter());
        $form->submit($submit);

        self::assertTrue($form->isSynchronized(), (string)$form->getTransformationFailure());
        self::assertSame($valid, $form->isValid(), (string)$form->getErrors(true, false));
        self::assertEquals($expected, $actual);
    }

    public static function submit(): \Generator
    {
        yield [
            [],
            new JobFilter(),
            true,
        ];

        yield [
            ['jobs' => [], 'statuses' => []],
            new JobFilter(),
            true,
        ];

        yield [
            ['jobs' => ['export'], 'statuses' => [BatchStatus::PENDING]],
            new JobFilter(['export'], [BatchStatus::PENDING]),
            true,
        ];

        yield [
            ['jobs' => ['unknown'], 'statuses' => [99]],
            new JobFilter(),
            false,
        ];
    }

    protected function getTypes(): array
    {
        return [
            new JobFilterType(['export', 'import']),
        ];
    }
}
