<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\UserInterface\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Yokai\Batch\BatchStatus;

/**
 * Filter form type for {@see JobExecution} model.
 */
final class JobFilterType extends AbstractType
{
    private const STATUSES = [
        'pending' => BatchStatus::PENDING,
        'running' => BatchStatus::RUNNING,
        'stopped' => BatchStatus::STOPPED,
        'completed' => BatchStatus::COMPLETED,
        'abandoned' => BatchStatus::ABANDONED,
        'failed' => BatchStatus::FAILED,
    ];

    public function __construct(
        /**
         * @var array<string>
         */
        private array $jobs,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'jobs',
            ChoiceType::class,
            [
                'label' => 'job.field.job_name',
                'choice_label' => fn($choice, string $key, $value) => sprintf('job.job_name.%s', $key),
                'choices' => \array_combine($this->jobs, $this->jobs),
                'required' => false,
                'multiple' => true,
            ],
        );
        $builder->add(
            'statuses',
            ChoiceType::class,
            [
                'label' => 'job.field.status',
                'choice_label' => fn($choice, string $key, $value) => sprintf('job.status.%s', $key),
                'choices' => self::STATUSES,
                'required' => false,
                'multiple' => true,
            ],
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('translation_domain', 'YokaiBatchBundle');
        $resolver->setDefault('data_class', JobFilter::class);
    }
}
