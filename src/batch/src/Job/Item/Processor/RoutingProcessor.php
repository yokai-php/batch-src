<?php

declare(strict_types=1);

namespace Yokai\Batch\Job\Item\Processor;

use Yokai\Batch\Exception\UnexpectedValueException;
use Yokai\Batch\Job\Item\ElementConfiguratorTrait;
use Yokai\Batch\Job\Item\FlushableInterface;
use Yokai\Batch\Job\Item\InitializableInterface;
use Yokai\Batch\Job\Item\ItemProcessorInterface;
use Yokai\Batch\Job\JobExecutionAwareInterface;
use Yokai\Batch\Job\JobExecutionAwareTrait;
use Yokai\Batch\Routing\RoutingInterface;

final class RoutingProcessor implements
    ItemProcessorInterface,
    InitializableInterface,
    FlushableInterface,
    JobExecutionAwareInterface
{
    use ElementConfiguratorTrait;
    use JobExecutionAwareTrait;

    private RoutingInterface $routing;

    /**
     * @var ItemProcessorInterface[]
     */
    private array $processors = [];

    public function __construct(RoutingInterface $routing)
    {
        $this->routing = $routing;
    }

    /**
     * @inheritdoc
     */
    public function process($item)
    {
        $processor = $this->routing->get($item);
        if (!$processor instanceof ItemProcessorInterface) {
            throw UnexpectedValueException::type(ItemProcessorInterface::class, $processor);
        }

        $processorId = \spl_object_hash($processor);

        if (!isset($this->processors[$processorId])) {
            $this->processors[$processorId] = $processor;
            $this->configureElementJobContext($processor, $this->jobExecution);
            $this->initializeElement($processor);
        }

        return $processor->process($item);
    }

    /**
     * @inheritdoc
     */
    public function initialize(): void
    {
        $this->processors = [];
    }

    /**
     * @inheritdoc
     */
    public function flush(): void
    {
        $processors = $this->processors;
        $this->processors = [];

        foreach ($processors as $processor) {
            $this->flushElement($processor);
        }
    }
}
