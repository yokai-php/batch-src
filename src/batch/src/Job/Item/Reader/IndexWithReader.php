<?php

declare(strict_types=1);

namespace Yokai\Batch\Job\Item\Reader;

use Yokai\Batch\Job\Item\ItemReaderInterface;

final class IndexWithReader implements ItemReaderInterface
{
    private ItemReaderInterface $reader;

    /**
     * @var callable
     */
    private $getIndex;

    public function __construct(ItemReaderInterface $reader, callable $getIndex)
    {
        $this->reader = $reader;
        $this->getIndex = $getIndex;
    }

    public static function withArrayKey(ItemReaderInterface $reader, string $key): self
    {
        return new self($reader, fn(array $item) => $item[$key]);
    }

    public static function withProperty(ItemReaderInterface $reader, string $property): self
    {
        return new self($reader, fn(object $item) => $item->$property);
    }

    public static function withGetter(ItemReaderInterface $reader, string $getter): self
    {
        return new self($reader, fn(object $item) => $item->$getter());
    }

    /**
     * @inheritdoc
     */
    public function read(): iterable
    {
        foreach ($this->reader->read() as $item) {
            yield ($this->getIndex)($item) => $item;
        }
    }
}
