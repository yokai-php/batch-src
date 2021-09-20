<?php

declare(strict_types=1);

namespace Yokai\Batch\Job\Item\Reader;

use Closure;
use Yokai\Batch\Job\Item\ItemReaderInterface;

/**
 * An {@see ItemReaderInterface} that decorates another {@see ItemReaderInterface}
 * and extract item index of each item using a {@see Closure}.
 *
 * Provided {@see Closure} must accept a single argument (the read item)
 * and must return a value (preferably unique) that will be item index.
 */
final class IndexWithReader implements ItemReaderInterface
{
    private ItemReaderInterface $reader;
    private Closure $extractItemIndex;

    public function __construct(ItemReaderInterface $reader, Closure $extractItemIndex)
    {
        $this->reader = $reader;
        $this->extractItemIndex = $extractItemIndex;
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
            yield ($this->extractItemIndex)($item) => $item;
        }
    }
}
